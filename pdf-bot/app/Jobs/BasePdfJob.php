<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\TaskJob;
use App\Services\Messaging\TwilioService;
use App\Services\StorageService;
use App\Services\FileDownloadService;
use App\Services\PdfMicroserviceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

abstract class BasePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Document $document;
    protected TaskJob $taskJob;
    protected string $replyTo;
    protected TwilioService $twilioService;
    protected StorageService $storageService;
    protected FileDownloadService $downloadService;
    protected PdfMicroserviceClient $microserviceClient;

    public function __construct(Document $document, TaskJob $taskJob, string $replyTo)
    {
        $this->document = $document;
        $this->taskJob = $taskJob;
        $this->replyTo = $replyTo;
    }

    public function handle(): void
    {
        $this->twilioService = app(TwilioService::class);
        $this->storageService = app(StorageService::class);
        $this->downloadService = app(FileDownloadService::class);
        $this->microserviceClient = app(PdfMicroserviceClient::class);

        Log::info('PDF job started', [
            'job_class' => get_class($this),
            'document_id' => $this->document->id,
            'task_job_id' => $this->taskJob->id,
            'type' => $this->taskJob->type
        ]);

        // Mark as running
        $this->taskJob->update([
            'status' => 'running',
            'started_at' => now()
        ]);

        try {
            // Step 1: Download PDF from Twilio media URL
            $localPath = $this->downloadPdfFromTwilio();
            
            // Step 2: Upload to S3/MinIO
            $s3Path = $this->uploadToStorage($localPath);
            
            // Step 3: Call Python microservice (placeholder for now)
            $resultPath = $this->processPdf($s3Path);
            
            // Step 4: Generate signed URL and send result
            $this->sendResult($resultPath);
            
            // Step 5: Mark as completed
            $this->markCompleted();

        } catch (\Exception $e) {
            $this->markFailed($e);
        } finally {
            // Clean up old temporary files
            try {
                $cleanedCount = $this->downloadService->cleanupTempFiles(60);
                if ($cleanedCount > 0) {
                    Log::info("Cleaned up {$cleanedCount} old temporary files");
                }
            } catch (\Exception $e) {
                Log::warning('Failed to cleanup temp files: ' . $e->getMessage());
            }
        }
    }

    /**
     * Download PDF from Twilio media URL
     */
    protected function downloadPdfFromTwilio(): string
    {
        $mediaUrl = $this->document->metadata['media_url'] ?? null;
        
        if (!$mediaUrl) {
            throw new \Exception('No media URL found in document metadata');
        }

        Log::info('Downloading PDF from Twilio', [
            'media_url' => $mediaUrl,
            'document_id' => $this->document->id
        ]);

        try {
            // Use FileDownloadService for secure download with validation
            $localPath = $this->downloadService->downloadFile($mediaUrl);
            
            Log::info('PDF downloaded successfully', [
                'local_path' => $localPath,
                'file_size' => filesize($localPath)
            ]);
            
            return $localPath;
            
        } catch (\Exception $e) {
            Log::error('Failed to download PDF from Twilio', [
                'media_url' => $mediaUrl,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Upload file to S3/MinIO storage
     */
    protected function uploadToStorage(string $localPath): string
    {
        $filename = $this->generateStorageFilename();
        $s3Path = 'documents/' . $filename;
        
        Log::info('Uploading PDF to storage', [
            'local_path' => $localPath,
            's3_path' => $s3Path,
            'document_id' => $this->document->id
        ]);

        // Upload to storage
        $fileContent = file_get_contents($localPath);
        Storage::disk(config('filesystems.default'))->put($s3Path, $fileContent, 'private');
        
        // Update document with S3 path and file size
        $this->document->update([
            's3_path' => $s3Path,
            'file_size' => filesize($localPath)
        ]);

        // Clean up local file
        unlink($localPath);

        return $s3Path;
    }

    /**
     * Process PDF using Python microservice
     */
    protected function processPdf(string $s3Path): string
    {
        Log::info('Processing PDF with microservice', [
            's3_path' => $s3Path,
            'job_type' => $this->taskJob->type,
            'parameters' => $this->taskJob->parameters
        ]);

        try {
            // Check if microservice is enabled
            if (!config('services.pdf_microservice.enabled')) {
                Log::info('PDF microservice disabled, using placeholder');
                return $this->createPlaceholderResult($s3Path);
            }

            // Call appropriate microservice method (handles S3 download/upload internally)
            $result = $this->callMicroservice();

            if ($result['success']) {
                // Update document with output info
                $this->document->update([
                    's3_output_path' => $result['s3_output_path'],
                    'output_file_size' => $result['size'],
                    'processing_metadata' => [
                        'content_type' => $result['content_type'] ?? 'application/pdf',
                        'microservice_response' => array_except($result, ['content'])
                    ]
                ]);

                Log::info('PDF processing completed successfully', [
                    'output_path' => $result['s3_output_path'],
                    'size' => $result['size']
                ]);

                return $result['s3_output_path'];
            } else {
                throw new \Exception('Microservice processing failed: ' . ($result['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('PDF microservice processing failed', [
                's3_path' => $s3Path,
                'error' => $e->getMessage()
            ]);

            // Fallback to placeholder
            Log::info('Falling back to placeholder result');
            return $this->createPlaceholderResult($s3Path);
        }
    }

    /**
     * Send result to user via WhatsApp
     */
    protected function sendResult(string $resultPath): void
    {
        try {
            // Generate temporary signed URL (valid for 1 hour)
            $signedUrl = $this->storageService->getTemporaryUrl($resultPath, 60);
            
            $caption = $this->getResultCaption();
            
            Log::info('Sending result via WhatsApp', [
                'result_path' => $resultPath,
                'signed_url' => $signedUrl,
                'reply_to' => $this->replyTo
            ]);

            // Send result file
            $sent = $this->twilioService->sendMedia($this->replyTo, $signedUrl, $caption);
            
            if (!$sent) {
                throw new \Exception('Failed to send result via WhatsApp');
            }

        } catch (\Exception $e) {
            Log::error('Failed to send result', [
                'error' => $e->getMessage(),
                'result_path' => $resultPath
            ]);
            
            // Fallback: send error message
            $this->twilioService->sendText(
                $this->replyTo,
                "❌ Traitement terminé mais erreur d'envoi. Veuillez réessayer."
            );
        }
    }

    /**
     * Mark job as completed
     */
    protected function markCompleted(): void
    {
        $processingTime = now()->diffInSeconds($this->taskJob->started_at);
        
        $this->taskJob->update([
            'status' => 'completed',
            'completed_at' => now(),
            'processing_time_seconds' => $processingTime,
            'result_metadata' => [
                'success' => true,
                'processing_time_seconds' => $processingTime,
                'output_path' => $this->document->s3_output_path
            ]
        ]);

        $this->document->update(['status' => 'completed']);

        Log::info('PDF job completed', [
            'job_class' => get_class($this),
            'document_id' => $this->document->id,
            'task_job_id' => $this->taskJob->id,
            'processing_time_seconds' => $processingTime
        ]);
    }

    /**
     * Mark job as failed
     */
    protected function markFailed(\Exception $e): void
    {
        $processingTime = $this->taskJob->started_at ? 
            now()->diffInSeconds($this->taskJob->started_at) : 0;

        $this->taskJob->update([
            'status' => 'failed',
            'completed_at' => now(),
            'processing_time_seconds' => $processingTime,
            'error_message' => $e->getMessage()
        ]);

        $this->document->update(['status' => 'failed']);

        Log::error('PDF job failed', [
            'job_class' => get_class($this),
            'document_id' => $this->document->id,
            'task_job_id' => $this->taskJob->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        // Send error message to user
        $this->twilioService->sendText(
            $this->replyTo,
            "❌ Erreur lors du traitement de votre PDF. Veuillez réessayer."
        );
    }

    /**
     * Validate if file is a valid PDF
     */
    protected function isValidPdf(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return false;
        }
        
        $header = fread($handle, 4);
        fclose($handle);
        
        return $header === '%PDF';
    }

    /**
     * Generate unique filename for storage
     */
    protected function generateStorageFilename(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $jobType = $this->taskJob->type;
        $hash = substr(md5($this->document->id . $timestamp), 0, 8);
        
        return "{$timestamp}_{$jobType}_{$hash}.pdf";
    }

    /**
     * Create placeholder result when microservice is unavailable
     */
    protected function createPlaceholderResult(string $s3Path): string
    {
        $resultContent = $this->createPlaceholderContent();
        $outputPath = $this->storageService->storeProcessedFile(
            $resultContent,
            $s3Path,
            '_' . $this->taskJob->type . $this->getPlaceholderFileExtension()
        );

        // Update document with placeholder info
        $this->document->update([
            's3_output_path' => $outputPath,
            'output_file_size' => strlen($resultContent),
            'processing_metadata' => [
                'type' => 'placeholder',
                'reason' => 'microservice_unavailable'
            ]
        ]);

        return $outputPath;
    }

    /**
     * Get file extension based on result type
     */
    protected function getResultFileExtension(array $result): string
    {
        $contentType = $result['content_type'] ?? '';
        
        if (str_contains($contentType, 'application/pdf')) {
            return '.pdf';
        } elseif (str_contains($contentType, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')) {
            return '.docx';
        } elseif (str_contains($contentType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
            return '.xlsx';
        } elseif (str_contains($contentType, 'text/plain')) {
            return '.txt';
        } elseif (str_contains($contentType, 'image/')) {
            return '.png';
        }
        
        return '.pdf'; // Default
    }

    /**
     * Abstract methods to be implemented by specific job classes
     */
    abstract protected function callMicroservice(): array;
    abstract protected function createPlaceholderContent(): string;
    abstract protected function getPlaceholderFileExtension(): string;
    abstract protected function getResultCaption(): string;
}
