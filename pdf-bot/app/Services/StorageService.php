<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class StorageService
{
    protected string $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.default');
    }

    /**
     * Upload a file to S3/MinIO storage
     */
    public function uploadFile(UploadedFile $file, string $directory = 'documents'): array
    {
        $filename = $this->generateUniqueFilename($file);
        $path = $directory . '/' . $filename;
        
        // Store the file
        $storedPath = Storage::disk($this->disk)->putFileAs(
            $directory,
            $file,
            $filename,
            'private'
        );

        return [
            'path' => $storedPath,
            'url' => $this->getTemporaryUrl($storedPath),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Generate a temporary signed URL (valid for specified minutes)
     */
    public function getTemporaryUrl(string $path, int $minutes = 60): string
    {
        try {
            return Storage::disk($this->disk)->temporaryUrl(
                $path,
                Carbon::now()->addMinutes($minutes)
            );
        } catch (\Exception $e) {
            // Fallback for local storage or unsupported drivers
            return Storage::disk($this->disk)->url($path);
        }
    }

    /**
     * Delete a file from storage
     */
    public function deleteFile(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * Get file size
     */
    public function getFileSize(string $path): int
    {
        return Storage::disk($this->disk)->size($path);
    }

    /**
     * Generate unique filename to avoid conflicts
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $hash = substr(md5($file->getClientOriginalName() . $timestamp), 0, 8);
        
        return "{$timestamp}_{$hash}.{$extension}";
    }

    /**
     * Store processed output file
     */
    public function storeProcessedFile(string $content, string $originalPath, string $suffix = '_processed'): string
    {
        $pathInfo = pathinfo($originalPath);
        $newFilename = $pathInfo['filename'] . $suffix . '.' . ($pathInfo['extension'] ?? 'pdf');
        $outputPath = $pathInfo['dirname'] . '/' . $newFilename;
        
        Storage::disk($this->disk)->put($outputPath, $content, 'private');
        
        return $outputPath;
    }

    /**
     * Store file content directly to S3
     */
    public function storeFileContent(string $path, string $content, string $mimeType = 'application/octet-stream'): bool
    {
        try {
            $result = Storage::disk($this->disk)->put($path, $content, [
                'visibility' => 'private',
                'ContentType' => $mimeType,
            ]);
            
            Log::info('File content stored successfully', [
                'path' => $path,
                'size' => strlen($content),
                'mime_type' => $mimeType,
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Failed to store file content', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
