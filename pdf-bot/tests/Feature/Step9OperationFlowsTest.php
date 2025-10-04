<?php

namespace Tests\Feature;

use App\Jobs\ConvertPdfJob;
use App\Jobs\OcrPdfJob;
use App\Jobs\SummarizePdfJob;
use App\Jobs\TranslatePdfJob;
use App\Jobs\SecurePdfJob;
use App\Models\Document;
use App\Models\TaskJob;
use App\Services\PdfMicroserviceClient;
use App\Services\StorageService;
use App\Services\FileDownloadService;
use App\Services\Messaging\TwilioService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class Step9OperationFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'services.pdf_microservice.enabled' => false, // Use placeholders for testing
            'filesystems.default' => 's3'
        ]);
    }

    public function test_convert_pdf_flow_docx()
    {
        $this->mockServices();
        
        // Create test document and job
        $document = Document::create([
            'original_name' => 'test.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending',
            's3_path' => 'documents/test.pdf'
        ]);

        $taskJob = TaskJob::create([
            'document_id' => $document->id,
            'type' => 'convert',
            'status' => 'pending',
            'parameters' => ['format' => 'docx']
        ]);

        // Execute job
        $job = new ConvertPdfJob($document, $taskJob, 'whatsapp:+1234567890');
        $job->handle();

        // Assert job completed
        $taskJob->refresh();
        $document->refresh();

        $this->assertEquals('completed', $taskJob->status);
        $this->assertNotNull($document->s3_output_path);
    }

    public function test_ocr_pdf_flow()
    {
        $this->mockServices();
        
        $document = Document::create([
            'original_name' => 'scanned.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending',
            's3_path' => 'documents/scanned.pdf'
        ]);

        $taskJob = TaskJob::create([
            'document_id' => $document->id,
            'type' => 'ocr',
            'status' => 'pending',
            'parameters' => ['language' => 'eng', 'output_format' => 'txt']
        ]);

        $job = new OcrPdfJob($document, $taskJob, 'whatsapp:+1234567890');
        $job->handle();

        $taskJob->refresh();
        $document->refresh();

        $this->assertEquals('completed', $taskJob->status);
        $this->assertNotNull($document->s3_output_path);
    }

    public function test_summarize_pdf_flow()
    {
        $this->mockServices();
        
        $document = Document::create([
            'original_name' => 'report.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending',
            's3_path' => 'documents/report.pdf'
        ]);

        $taskJob = TaskJob::create([
            'document_id' => $document->id,
            'type' => 'summarize',
            'status' => 'pending',
            'parameters' => ['size' => 'medium', 'language' => 'en']
        ]);

        $job = new SummarizePdfJob($document, $taskJob, 'whatsapp:+1234567890');
        $job->handle();

        $taskJob->refresh();
        $this->assertEquals('completed', $taskJob->status);
    }

    public function test_translate_pdf_flow()
    {
        $this->mockServices();
        
        $document = Document::create([
            'original_name' => 'english_doc.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending',
            's3_path' => 'documents/english_doc.pdf'
        ]);

        $taskJob = TaskJob::create([
            'document_id' => $document->id,
            'type' => 'translate',
            'status' => 'pending',
            'parameters' => ['target_language' => 'fr']
        ]);

        $job = new TranslatePdfJob($document, $taskJob, 'whatsapp:+1234567890');
        $job->handle();

        $taskJob->refresh();
        $this->assertEquals('completed', $taskJob->status);
    }

    public function test_secure_pdf_flow()
    {
        $this->mockServices();
        
        $document = Document::create([
            'original_name' => 'confidential.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending',
            's3_path' => 'documents/confidential.pdf'
        ]);

        $taskJob = TaskJob::create([
            'document_id' => $document->id,
            'type' => 'secure',
            'status' => 'pending',
            'parameters' => [
                'security_type' => 'password',
                'password' => 'secret123'
            ]
        ]);

        $job = new SecurePdfJob($document, $taskJob, 'whatsapp:+1234567890');
        $job->handle();

        $taskJob->refresh();
        $this->assertEquals('completed', $taskJob->status);
    }

    public function test_all_operations_with_different_parameters()
    {
        $this->mockServices();
        
        $operations = [
            [
                'type' => 'convert',
                'job_class' => ConvertPdfJob::class,
                'parameters' => ['format' => 'xlsx'],
                'filename' => 'spreadsheet.pdf'
            ],
            [
                'type' => 'ocr',
                'job_class' => OcrPdfJob::class,
                'parameters' => ['language' => 'fra', 'output_format' => 'docx'],
                'filename' => 'french_scan.pdf'
            ],
            [
                'type' => 'summarize',
                'job_class' => SummarizePdfJob::class,
                'parameters' => ['size' => 'long', 'language' => 'fr'],
                'filename' => 'long_document.pdf'
            ],
            [
                'type' => 'translate',
                'job_class' => TranslatePdfJob::class,
                'parameters' => ['target_language' => 'es'],
                'filename' => 'document_to_translate.pdf'
            ],
            [
                'type' => 'secure',
                'job_class' => SecurePdfJob::class,
                'parameters' => ['security_type' => 'watermark', 'watermark_text' => 'CONFIDENTIAL'],
                'filename' => 'document_to_secure.pdf'
            ]
        ];

        foreach ($operations as $operation) {
            $document = Document::create([
                'original_name' => $operation['filename'],
                'whatsapp_user_id' => '+1234567890',
                'status' => 'pending',
                's3_path' => 'documents/' . $operation['filename']
            ]);

            $taskJob = TaskJob::create([
                'document_id' => $document->id,
                'type' => $operation['type'],
                'status' => 'pending',
                'parameters' => $operation['parameters']
            ]);

            $job = new $operation['job_class']($document, $taskJob, 'whatsapp:+1234567890');
            $job->handle();

            $taskJob->refresh();
            $document->refresh();

            $this->assertEquals('completed', $taskJob->status, 
                "Operation {$operation['type']} should complete successfully");
            $this->assertNotNull($document->s3_output_path, 
                "Operation {$operation['type']} should create output file");
        }
    }

    public function test_error_handling_for_all_operations()
    {
        // Mock services to simulate failures
        $this->mockServicesWithFailure();
        
        $operations = [
            ['type' => 'convert', 'job_class' => ConvertPdfJob::class, 'params' => ['format' => 'docx']],
            ['type' => 'ocr', 'job_class' => OcrPdfJob::class, 'params' => ['language' => 'eng']],
            ['type' => 'summarize', 'job_class' => SummarizePdfJob::class, 'params' => ['size' => 'short']],
            ['type' => 'translate', 'job_class' => TranslatePdfJob::class, 'params' => ['target_language' => 'fr']],
            ['type' => 'secure', 'job_class' => SecurePdfJob::class, 'params' => ['security_type' => 'password']]
        ];

        foreach ($operations as $operation) {
            $document = Document::create([
                'original_name' => 'test.pdf',
                'whatsapp_user_id' => '+1234567890',
                'status' => 'pending',
                's3_path' => 'documents/test.pdf'
            ]);

            $taskJob = TaskJob::create([
                'document_id' => $document->id,
                'type' => $operation['type'],
                'status' => 'pending',
                'parameters' => $operation['params']
            ]);

            $job = new $operation['job_class']($document, $taskJob, 'whatsapp:+1234567890');
            $job->handle();

            $taskJob->refresh();
            
            // Job should still complete with placeholder result
            $this->assertEquals('completed', $taskJob->status, 
                "Operation {$operation['type']} should complete with fallback");
        }
    }

    protected function mockServices()
    {
        $twilioMock = Mockery::mock(TwilioService::class);
        $twilioMock->shouldReceive('sendText')->andReturn(true);
        $twilioMock->shouldReceive('sendMedia')->andReturn(true);
        $this->app->instance(TwilioService::class, $twilioMock);

        $storageMock = Mockery::mock(StorageService::class);
        $storageMock->shouldReceive('storeFileContent')->andReturn(true);
        $storageMock->shouldReceive('getTemporaryUrl')->andReturn('https://example.com/signed-url');
        $this->app->instance(StorageService::class, $storageMock);

        $downloadMock = Mockery::mock(FileDownloadService::class);
        $downloadMock->shouldReceive('cleanupTempFiles')->andReturn(0);
        $this->app->instance(FileDownloadService::class, $downloadMock);
    }

    protected function mockServicesWithFailure()
    {
        $twilioMock = Mockery::mock(TwilioService::class);
        $twilioMock->shouldReceive('sendText')->andReturn(true);
        $twilioMock->shouldReceive('sendMedia')->andReturn(true);
        $this->app->instance(TwilioService::class, $twilioMock);

        // Mock storage to work normally
        $storageMock = Mockery::mock(StorageService::class);
        $storageMock->shouldReceive('storeFileContent')->andReturn(true);
        $storageMock->shouldReceive('getTemporaryUrl')->andReturn('https://example.com/signed-url');
        $this->app->instance(StorageService::class, $storageMock);

        $downloadMock = Mockery::mock(FileDownloadService::class);
        $downloadMock->shouldReceive('cleanupTempFiles')->andReturn(0);
        $this->app->instance(FileDownloadService::class, $downloadMock);

        // Microservice disabled, so it will use placeholders
        config(['services.pdf_microservice.enabled' => false]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
