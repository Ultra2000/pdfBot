<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\StorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StorageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Override filesystem disk for testing
        config(['filesystems.default' => 'local']);
        
        $this->storageService = app(StorageService::class);
        
        // Use fake storage for testing
        Storage::fake('local');
    }

    public function test_upload_file_returns_correct_data()
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024);
        
        $result = $this->storageService->uploadFile($file, 'test-directory');
        
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('size', $result);
        $this->assertArrayHasKey('mime_type', $result);
        $this->assertArrayHasKey('original_name', $result);
        
        $this->assertEquals('test.pdf', $result['original_name']);
        $this->assertEquals($file->getSize(), $result['size']);
        $this->assertStringStartsWith('test-directory/', $result['path']);
    }

    public function test_file_exists_check()
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024);
        $result = $this->storageService->uploadFile($file);
        
        $this->assertTrue($this->storageService->fileExists($result['path']));
        $this->assertFalse($this->storageService->fileExists('non-existent-file.pdf'));
    }

    public function test_delete_file()
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024);
        $result = $this->storageService->uploadFile($file);
        
        $this->assertTrue($this->storageService->fileExists($result['path']));
        
        $deleted = $this->storageService->deleteFile($result['path']);
        
        $this->assertTrue($deleted);
        $this->assertFalse($this->storageService->fileExists($result['path']));
    }

    public function test_store_processed_file()
    {
        $originalPath = 'documents/test.pdf';
        $content = 'Processed PDF content';
        
        $outputPath = $this->storageService->storeProcessedFile($content, $originalPath, '_compressed');
        
        $this->assertEquals('documents/test_compressed.pdf', $outputPath);
        $this->assertTrue($this->storageService->fileExists($outputPath));
    }
}
