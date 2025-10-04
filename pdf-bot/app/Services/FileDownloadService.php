<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileDownloadService
{
    protected int $maxFileSize;
    protected array $allowedMimeTypes;
    protected int $timeoutSeconds;

    public function __construct()
    {
        $this->maxFileSize = config('app.max_pdf_size', 50 * 1024 * 1024); // 50MB default
        $this->allowedMimeTypes = ['application/pdf'];
        $this->timeoutSeconds = config('app.download_timeout', 120); // 2 minutes
    }

    /**
     * Download file from URL with validation
     */
    public function downloadFile(string $url, string $destinationPath = null): string
    {
        Log::info('Starting file download', ['url' => $url]);

        // Create destination path if not provided
        if (!$destinationPath) {
            $destinationPath = storage_path('app/temp/' . uniqid('download_') . '.pdf');
        }

        // Ensure temp directory exists
        $tempDir = dirname($destinationPath);
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Download with size and timeout limits
        $response = Http::timeout($this->timeoutSeconds)
            ->withOptions([
                'stream' => true,
                'read_timeout' => $this->timeoutSeconds,
                'connect_timeout' => 30
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new \Exception("Failed to download file: HTTP {$response->status()}");
        }

        // Get the response body as a stream
        $stream = $response->getBody();
        $file = fopen($destinationPath, 'w');
        $totalSize = 0;

        // Stream the file while checking size limits
        while (!$stream->eof()) {
            $chunk = $stream->read(8192); // 8KB chunks
            $totalSize += strlen($chunk);
            
            // Check size limit
            if ($totalSize > $this->maxFileSize) {
                fclose($file);
                unlink($destinationPath);
                throw new \Exception("File too large. Maximum size: " . $this->formatBytes($this->maxFileSize));
            }
            
            fwrite($file, $chunk);
        }

        fclose($file);

        // Validate the downloaded file
        $this->validateFile($destinationPath);

        Log::info('File downloaded successfully', [
            'destination' => $destinationPath,
            'size' => $totalSize,
            'size_formatted' => $this->formatBytes($totalSize)
        ]);

        return $destinationPath;
    }

    /**
     * Validate downloaded file
     */
    protected function validateFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \Exception('Downloaded file does not exist');
        }

        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            unlink($filePath);
            throw new \Exception('Downloaded file is empty');
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            unlink($filePath);
            throw new \Exception("Invalid file type: {$mimeType}. Expected: " . implode(', ', $this->allowedMimeTypes));
        }

        // Additional PDF validation
        if ($mimeType === 'application/pdf') {
            $this->validatePdf($filePath);
        }
    }

    /**
     * Validate PDF file structure
     */
    protected function validatePdf(string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Cannot open PDF file for validation');
        }

        // Check PDF header
        $header = fread($handle, 4);
        if ($header !== '%PDF') {
            fclose($handle);
            unlink($filePath);
            throw new \Exception('Invalid PDF file: missing PDF header');
        }

        // Check if file is not corrupted by seeking to end
        fseek($handle, -1024, SEEK_END);
        $footer = fread($handle, 1024);
        fclose($handle);

        if (strpos($footer, '%%EOF') === false) {
            unlink($filePath);
            throw new \Exception('Invalid PDF file: missing EOF marker');
        }
    }

    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles(int $olderThanMinutes = 60): int
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            return 0;
        }

        $deletedCount = 0;
        $cutoffTime = time() - ($olderThanMinutes * 60);

        $files = glob($tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deletedCount++;
                    Log::info('Cleaned up temp file', ['file' => $file]);
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get max file size
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * Get max file size formatted
     */
    public function getMaxFileSizeFormatted(): string
    {
        return $this->formatBytes($this->maxFileSize);
    }

    /**
     * Download file from S3/MinIO storage to temporary location
     */
    public function downloadFromStorage(string $s3Path): string
    {
        Log::info('Downloading file from storage', ['s3_path' => $s3Path]);

        try {
            // Get file contents from storage
            $contents = Storage::disk('s3')->get($s3Path);
            
            if (!$contents) {
                throw new \Exception("File not found in storage: {$s3Path}");
            }

            // Create temporary file
            $tempPath = tempnam(sys_get_temp_dir(), 'pdf_download_');
            
            // Write contents to temp file
            file_put_contents($tempPath, $contents);
            
            Log::info('File downloaded from storage successfully', [
                's3_path' => $s3Path,
                'temp_path' => $tempPath,
                'size' => strlen($contents)
            ]);

            return $tempPath;

        } catch (\Exception $e) {
            Log::error('Failed to download file from storage', [
                's3_path' => $s3Path,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
