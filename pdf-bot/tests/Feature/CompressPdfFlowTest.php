<?php

namespace Tests\Feature;

use App\Jobs\CompressPdfJob;
use App\Models\Document;
use App\Models\TaskJob;
use App\Services\Messaging\TwilioService;
use App\Services\PdfMicroserviceClient;
use App\Services\StorageService;
use App\Services\FileDownloadService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;

class CompressPdfFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock configuration for testing
        config([
            'services.twilio.sid' => 'test_sid',
            'services.twilio.auth_token' => 'test_token',
            'services.twilio.whatsapp_number' => 'whatsapp:+14155238886',
            'services.pdf_microservice.url' => 'http://localhost:8000',
            'services.pdf_microservice.enabled' => true,
            'services.pdf_microservice.timeout' => 60,
            'filesystems.default' => 's3'
        ]);
    }

    public function test_complete_compress_pdf_flow_happy_path()
    {
        // Arrange: Mock external dependencies
        $this->mockTwilioService();
        $this->mockFileDownload();
        $this->mockS3Storage();
        $this->mockMicroservice();

        // Act: Send webhook request with PDF and COMPRESS command
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'COMPRESS whatsapp',
            'MediaUrl0' => 'https://api.twilio.com/test-pdf-url',
            'MediaContentType0' => 'application/pdf'
        ]);

        // Assert: Webhook response is successful
        $this->assertEquals(200, $response->getStatusCode());

        // Assert: Document and TaskJob are created
        $this->assertDatabaseHas('documents', [
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('task_jobs', [
            'type' => 'compress',
            'status' => 'pending'
        ]);

        // Get created records
        $document = Document::where('whatsapp_user_id', '+1234567890')->first();
        $taskJob = TaskJob::where('type', 'compress')->first();

        $this->assertNotNull($document);
        $this->assertNotNull($taskJob);
        $this->assertEquals($document->id, $taskJob->document_id);
        $this->assertEquals('whatsapp', $taskJob->parameters['mode']);

        // Manually execute the job (since we're testing the full flow)
        $compressJob = new CompressPdfJob($document, $taskJob, 'whatsapp:+1234567890');
        $compressJob->handle();

        // Assert: Job processing completed successfully
        $taskJob->refresh();
        $document->refresh();

        $this->assertEquals('completed', $taskJob->status);
        $this->assertNotNull($taskJob->completed_at);
        $this->assertTrue($taskJob->processing_time_seconds >= 0);

        // Assert: Document has S3 paths
        $this->assertNotNull($document->s3_path);
        $this->assertNotNull($document->s3_output_path);
        $this->assertEquals('completed', $document->status);
    }

    public function test_compress_pdf_flow_with_microservice_failure()
    {
        // Arrange: Mock services with microservice failure
        $this->mockTwilioService();
        $this->mockFileDownload();
        $this->mockS3Storage();
        $this->mockMicroserviceFailure();

        // Act: Send webhook request
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'COMPRESS impression',
            'MediaUrl0' => 'https://api.twilio.com/test-pdf-url',
            'MediaContentType0' => 'application/pdf'
        ]);

        // Assert: Webhook still successful
        $this->assertEquals(200, $response->getStatusCode());

        // Get created records
        $document = Document::where('whatsapp_user_id', '+1234567890')->first();
        $taskJob = TaskJob::where('type', 'compress')->first();

        // Execute job
        $compressJob = new CompressPdfJob($document, $taskJob, 'whatsapp:+1234567890');
        $compressJob->handle();

        // Assert: Job falls back to placeholder
        $taskJob->refresh();
        $document->refresh();

        $this->assertEquals('completed', $taskJob->status);
        $this->assertEquals('completed', $document->status);
        $this->assertNotNull($document->s3_output_path);
    }

    public function test_compress_pdf_flow_invalid_commands()
    {
        // Test invalid command
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'INVALID_COMMAND',
            'MediaUrl0' => 'https://api.twilio.com/test-pdf-url',
            'MediaContentType0' => 'application/pdf'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertDatabaseMissing('task_jobs', ['type' => 'compress']);
    }

    public function test_compress_pdf_flow_different_modes()
    {
        $this->mockTwilioService();
        $this->mockFileDownload();
        $this->mockS3Storage();
        $this->mockMicroservice();

        $modes = ['whatsapp', 'impression', 'équilibré'];

        foreach ($modes as $mode) {
            $response = $this->postJson('/api/whatsapp/webhook', [
                'From' => "whatsapp:+123456789{$mode}",
                'Body' => "COMPRESS {$mode}",
                'MediaUrl0' => 'https://api.twilio.com/test-pdf-url',
                'MediaContentType0' => 'application/pdf'
            ]);

            $this->assertEquals(200, $response->getStatusCode());
            
            $this->assertDatabaseHas('task_jobs', [
                'type' => 'compress',
                'parameters->mode' => $mode
            ]);
        }
    }

    protected function mockTwilioService()
    {
        $twilioMock = Mockery::mock(TwilioService::class);
        $twilioMock->shouldReceive('sendText')->andReturn(true);
        $twilioMock->shouldReceive('sendMedia')->andReturn(true);
        $this->app->instance(TwilioService::class, $twilioMock);
    }

    protected function mockFileDownload()
    {
        $downloadMock = Mockery::mock(FileDownloadService::class);
        $downloadMock->shouldReceive('downloadFromUrl')
            ->andReturn('/tmp/downloaded_file.pdf');
        $downloadMock->shouldReceive('downloadFromStorage')
            ->andReturn('/tmp/s3_file.pdf');
        $downloadMock->shouldReceive('cleanupTempFiles')
            ->andReturn(0);
        $this->app->instance(FileDownloadService::class, $downloadMock);
    }

    protected function mockS3Storage()
    {
        $storageMock = Mockery::mock(StorageService::class);
        $storageMock->shouldReceive('uploadFile')
            ->andReturn([
                'path' => 'documents/test-file.pdf',
                'url' => 'https://s3.amazonaws.com/bucket/documents/test-file.pdf',
                'size' => 1024,
                'mime_type' => 'application/pdf'
            ]);
        $storageMock->shouldReceive('storeFileContent')
            ->andReturn(true);
        $storageMock->shouldReceive('getTemporaryUrl')
            ->andReturn('https://s3.amazonaws.com/bucket/signed-url');
        $this->app->instance(StorageService::class, $storageMock);
    }

    protected function mockMicroservice()
    {
        $microserviceMock = Mockery::mock(PdfMicroserviceClient::class);
        $microserviceMock->shouldReceive('compressPdf')
            ->andReturn([
                'success' => true,
                'file_path' => '/tmp/compressed.pdf',
                'size' => 512,
                'content_type' => 'application/pdf',
                's3_output_path' => 'processed/compress/compressed_file.pdf',
                's3_output_url' => 'https://s3.amazonaws.com/bucket/processed/compress/compressed_file.pdf'
            ]);
        $this->app->instance(PdfMicroserviceClient::class, $microserviceMock);
    }

    protected function mockMicroserviceFailure()
    {
        // Mock microservice as disabled to test fallback
        config(['services.pdf_microservice.enabled' => false]);
        
        $microserviceMock = Mockery::mock(PdfMicroserviceClient::class);
        $microserviceMock->shouldReceive('compressPdf')
            ->andReturn([
                'success' => false,
                'error' => 'Microservice unavailable'
            ]);
        $this->app->instance(PdfMicroserviceClient::class, $microserviceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
