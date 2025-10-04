<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PdfMicroserviceClient
{
    protected string $baseUrl;
    protected int $timeout;
    protected array $defaultHeaders;
    protected StorageService $storageService;
    protected FileDownloadService $downloadService;

    public function __construct(
        StorageService $storageService,
        FileDownloadService $downloadService
    ) {
        $this->baseUrl = config('services.pdf_microservice.url', 'http://localhost:8000');
        $this->timeout = config('services.pdf_microservice.timeout', 60);
        $this->defaultHeaders = [
            'Accept' => 'application/json',
        ];
        $this->storageService = $storageService;
        $this->downloadService = $downloadService;
    }

    /**
     * Check if the microservice is healthy
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->defaultHeaders)
                ->get("{$this->baseUrl}/health");

            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'data' => $response->json(),
                ];
            }

            return [
                'status' => 'unhealthy',
                'error' => "HTTP {$response->status()}: {$response->body()}",
            ];

        } catch (Exception $e) {
            Log::error('PDF Microservice health check failed', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl,
            ]);

            return [
                'status' => 'unreachable',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Compress a PDF file
     */
    public function compressPdf(string $s3Path, array $options = []): array
    {
        $defaultOptions = [
            'mode' => 'whatsapp',  // whatsapp/print/balanced
            'quality' => 'medium', // low/medium/high
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->processS3File('compress', $s3Path, $options);
    }

    /**
     * Convert PDF to another format
     */
    public function convertPdf(string $s3Path, string $format, array $options = []): array
    {
        $supportedFormats = ['docx', 'xlsx', 'img', 'png', 'jpg', 'jpeg'];
        
        if (!in_array($format, $supportedFormats)) {
            throw new Exception("Unsupported format: {$format}");
        }

        $requestOptions = array_merge(['format' => $format], $options);

        return $this->processS3File('convert', $s3Path, $requestOptions);
    }

    /**
     * Extract text using OCR
     */
    public function extractTextOcr(string $s3Path, array $options = []): array
    {
        $defaultOptions = [
            'language' => 'eng',        // OCR language
            'output_format' => 'txt',   // txt/docx
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->processS3File('ocr', $s3Path, $options);
    }

    /**
     * Summarize PDF content
     */
    public function summarizePdf(string $s3Path, array $options = []): array
    {
        $defaultOptions = [
            'length' => 'medium',   // short/medium/long
            'language' => 'en',     // output language
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->processS3File('summarize', $s3Path, $options);
    }

    /**
     * Translate PDF content
     */
    public function translatePdf(string $s3Path, string $targetLanguage, array $options = []): array
    {
        $defaultOptions = [
            'target_language' => $targetLanguage,
            'source_language' => 'auto',
            'output_format' => 'txt',
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->processS3File('translate', $s3Path, $options);
    }

    /**
     * Secure PDF with password and/or watermark
     */
    public function securePdf(string $s3Path, string $securityType, ?string $password = null, ?string $watermarkText = null): array
    {
        $options = [
            'security_type' => $securityType,
        ];

        if ($password) {
            $options['password'] = $password;
        }

        if ($watermarkText) {
            $options['watermark_text'] = $watermarkText;
        }

        return $this->processS3File('secure', $s3Path, $options);
    }

    /**
     * Process S3 file: download -> call microservice -> upload result to S3
     */
    protected function processS3File(string $endpoint, string $s3Path, array $options = []): array
    {
        $tempFilePath = null;
        $resultFilePath = null;

        try {
            Log::info("Processing S3 file through microservice", [
                'endpoint' => $endpoint,
                's3_path' => $s3Path,
                'options' => $options,
            ]);

            // Step 1: Download file from S3 to temporary location
            $tempFilePath = $this->downloadService->downloadFromStorage($s3Path);
            
            // Step 2: Call microservice with local file
            $microserviceResult = $this->makeRequest($endpoint, $tempFilePath, $options);
            
            // Step 3: If microservice returned a file, upload it to S3
            if (isset($microserviceResult['file_path']) && file_exists($microserviceResult['file_path'])) {
                $resultFilePath = $microserviceResult['file_path'];
                
                // Generate S3 path for result
                $originalName = pathinfo($s3Path, PATHINFO_FILENAME);
                $extension = pathinfo($resultFilePath, PATHINFO_EXTENSION);
                $outputS3Path = "processed/{$endpoint}/" . uniqid($originalName . '_') . ".{$extension}";
                
                // Upload result to S3
                $uploadResult = $this->uploadFileToS3($resultFilePath, $outputS3Path);
                
                $microserviceResult['s3_output_path'] = $outputS3Path;
                $microserviceResult['s3_output_url'] = $uploadResult['url'];
                
                Log::info("File processed and uploaded to S3", [
                    'endpoint' => $endpoint,
                    'input_s3_path' => $s3Path,
                    'output_s3_path' => $outputS3Path,
                ]);
            }

            return $microserviceResult;

        } catch (Exception $e) {
            Log::error("Failed to process S3 file through microservice", [
                'endpoint' => $endpoint,
                's3_path' => $s3Path,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
            
        } finally {
            // Cleanup temporary files
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            if ($resultFilePath && file_exists($resultFilePath) && $resultFilePath !== $tempFilePath) {
                unlink($resultFilePath);
            }
        }
    }

    /**
     * Upload a file to S3 storage
     */
    protected function uploadFileToS3(string $filePath, string $s3Path): array
    {
        try {
            // Read file contents
            $contents = file_get_contents($filePath);
            $mimeType = mime_content_type($filePath);
            
            // Upload to S3
            $uploaded = $this->storageService->storeFileContent($s3Path, $contents, $mimeType);
            
            // Get temporary URL for immediate access
            $temporaryUrl = $this->storageService->getTemporaryUrl($s3Path, 1440); // 24 hours
            
            return [
                'path' => $s3Path,
                'url' => $temporaryUrl,
                'size' => strlen($contents),
                'mime_type' => $mimeType,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to upload file to S3', [
                'file_path' => $filePath,
                's3_path' => $s3Path,
                'error' => $e->getMessage(),
            ]);
            
            throw new Exception("Failed to upload file to S3: {$e->getMessage()}");
        }
    }

    /**
     * Make HTTP request to microservice
     */
    protected function makeRequest(string $endpoint, string $filePath, array $options = []): array
    {
        try {
            Log::info("PDF Microservice request: {$endpoint}", [
                'file' => basename($filePath),
                'options' => $options,
            ]);

            if (!file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            // Prepare file for upload
            $fileResource = fopen($filePath, 'r');
            $fileName = basename($filePath);

            $response = Http::timeout($this->timeout)
                ->withHeaders($this->defaultHeaders)
                ->attach('file', $fileResource, $fileName)
                ->post("{$this->baseUrl}/{$endpoint}", $options);

            // Close file resource
            if (is_resource($fileResource)) {
                fclose($fileResource);
            }

            if ($response->successful()) {
                // Handle file response
                $contentType = $response->header('Content-Type');
                
                if (str_contains($contentType, 'application/') || str_contains($contentType, 'text/')) {
                    // This is a file response
                    return [
                        'success' => true,
                        'content' => $response->body(),
                        'content_type' => $contentType,
                        'filename' => $this->extractFilenameFromResponse($response),
                        'size' => strlen($response->body()),
                    ];
                } else {
                    // JSON response
                    return [
                        'success' => true,
                        'data' => $response->json(),
                    ];
                }
            } else {
                $error = $response->json()['detail'] ?? $response->body();
                
                Log::error("PDF Microservice error: {$endpoint}", [
                    'status' => $response->status(),
                    'error' => $error,
                    'file' => basename($filePath),
                ]);

                return [
                    'success' => false,
                    'error' => $error,
                    'status' => $response->status(),
                ];
            }

        } catch (Exception $e) {
            Log::error("PDF Microservice request failed: {$endpoint}", [
                'error' => $e->getMessage(),
                'file' => basename($filePath),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract filename from response headers
     */
    protected function extractFilenameFromResponse(Response $response): string
    {
        $contentDisposition = $response->header('Content-Disposition');
        
        if ($contentDisposition && preg_match('/filename="([^"]+)"/', $contentDisposition, $matches)) {
            return $matches[1];
        }

        return 'processed_file';
    }

    /**
     * Get supported languages for OCR
     */
    public function getSupportedOcrLanguages(): array
    {
        return [
            'eng' => 'English',
            'fra' => 'French',
            'spa' => 'Spanish',
            'deu' => 'German',
            'ita' => 'Italian',
            'por' => 'Portuguese',
            'rus' => 'Russian',
            'chi_sim' => 'Chinese Simplified',
            'chi_tra' => 'Chinese Traditional',
            'jpn' => 'Japanese',
            'kor' => 'Korean',
        ];
    }

    /**
     * Get supported translation languages
     */
    public function getSupportedTranslationLanguages(): array
    {
        return [
            'en' => 'English',
            'fr' => 'French',
            'es' => 'Spanish',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
        ];
    }

    /**
     * Get microservice status and configuration
     */
    public function getServiceInfo(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->defaultHeaders)
                ->get($this->baseUrl);

            if ($response->successful()) {
                return [
                    'available' => true,
                    'info' => $response->json(),
                    'url' => $this->baseUrl,
                ];
            }

            return [
                'available' => false,
                'error' => "HTTP {$response->status()}",
                'url' => $this->baseUrl,
            ];

        } catch (Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
                'url' => $this->baseUrl,
            ];
        }
    }
}
