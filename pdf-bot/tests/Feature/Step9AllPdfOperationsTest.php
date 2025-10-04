<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\TaskJob;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class Step9AllPdfOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'services.twilio.sid' => 'test_sid',
            'services.twilio.auth_token' => 'test_token',
            'services.twilio.whatsapp_number' => 'whatsapp:+14155238886',
        ]);
    }

    public function test_convert_pdf_command_flow()
    {
        \Queue::fake();
        
        $formats = ['docx', 'xlsx', 'img'];
        
        foreach ($formats as $format) {
            $response = $this->postJson('/api/whatsapp/webhook', [
                'From' => "whatsapp:+123456{$format}",
                'Body' => "CONVERT {$format}",
                'MediaUrl0' => 'https://example.com/test.pdf',
                'MediaContentType0' => 'application/pdf'
            ]);

            $this->assertEquals(200, $response->getStatusCode());
            
            $this->assertDatabaseHas('task_jobs', [
                'type' => 'convert',
                'parameters->format' => $format
            ]);
        }
        
        // Verify the correct job class is dispatched
        \Queue::assertPushed(\App\Jobs\ConvertPdfJob::class, 3);
    }

    public function test_ocr_pdf_command_flow()
    {
        \Queue::fake();
        
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'OCR',
            'MediaUrl0' => 'https://example.com/test.pdf',
            'MediaContentType0' => 'application/pdf'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertDatabaseHas('documents', [
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('task_jobs', [
            'type' => 'ocr',
            'status' => 'pending'
        ]);
        
        \Queue::assertPushed(\App\Jobs\OcrPdfJob::class);
    }

    public function test_summarize_pdf_command_flow()
    {
        \Queue::fake();
        
        $sizes = ['short', 'medium', 'long'];
        
        foreach ($sizes as $size) {
            $response = $this->postJson('/api/whatsapp/webhook', [
                'From' => "whatsapp:+123456{$size}",
                'Body' => "SUMMARIZE {$size}",
                'MediaUrl0' => 'https://example.com/test.pdf',
                'MediaContentType0' => 'application/pdf'
            ]);

            $this->assertEquals(200, $response->getStatusCode());
            
            $this->assertDatabaseHas('task_jobs', [
                'type' => 'summarize',
                'parameters->size' => $size
            ]);
        }
        
        \Queue::assertPushed(\App\Jobs\SummarizePdfJob::class, 3);
    }

    public function test_translate_pdf_command_flow()
    {
        \Queue::fake();
        
        $languages = ['fr', 'en', 'es', 'de'];
        
        foreach ($languages as $lang) {
            $response = $this->postJson('/api/whatsapp/webhook', [
                'From' => "whatsapp:+123456{$lang}",
                'Body' => "TRANSLATE {$lang}",
                'MediaUrl0' => 'https://example.com/test.pdf',
                'MediaContentType0' => 'application/pdf'
            ]);

            $this->assertEquals(200, $response->getStatusCode());
            
            $this->assertDatabaseHas('task_jobs', [
                'type' => 'translate',
                'parameters->target_language' => $lang
            ]);
        }
        
        \Queue::assertPushed(\App\Jobs\TranslatePdfJob::class, 4);
    }

    public function test_secure_pdf_command_flow()
    {
        \Queue::fake();
        
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'SECURE password',
            'MediaUrl0' => 'https://example.com/test.pdf',
            'MediaContentType0' => 'application/pdf'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertDatabaseHas('task_jobs', [
            'type' => 'secure',
            'parameters->security_type' => 'password'
        ]);
        
        \Queue::assertPushed(\App\Jobs\SecurePdfJob::class);
    }

    public function test_all_pdf_operations_create_proper_records()
    {
        \Queue::fake();
        
        $commands = [
            'COMPRESS whatsapp' => ['type' => 'compress', 'job' => \App\Jobs\CompressPdfJob::class],
            'CONVERT docx' => ['type' => 'convert', 'job' => \App\Jobs\ConvertPdfJob::class],
            'OCR' => ['type' => 'ocr', 'job' => \App\Jobs\OcrPdfJob::class],
            'SUMMARIZE medium' => ['type' => 'summarize', 'job' => \App\Jobs\SummarizePdfJob::class],
            'TRANSLATE fr' => ['type' => 'translate', 'job' => \App\Jobs\TranslatePdfJob::class],
            'SECURE password' => ['type' => 'secure', 'job' => \App\Jobs\SecurePdfJob::class],
        ];

        foreach ($commands as $command => $expected) {
            $response = $this->postJson('/api/whatsapp/webhook', [
                'From' => "whatsapp:+123456{$expected['type']}",
                'Body' => $command,
                'MediaUrl0' => 'https://example.com/test.pdf',
                'MediaContentType0' => 'application/pdf'
            ]);

            $this->assertEquals(200, $response->getStatusCode());
            
            // Check document creation
            $this->assertDatabaseHas('documents', [
                'whatsapp_user_id' => "+123456{$expected['type']}",
                'status' => 'pending'
            ]);
            
            // Check task job creation
            $this->assertDatabaseHas('task_jobs', [
                'type' => $expected['type'],
                'status' => 'pending'
            ]);
            
            // Verify correct job dispatched
            \Queue::assertPushed($expected['job']);
        }
    }

    public function test_command_parser_handles_all_operations()
    {
        $parser = app(\App\Support\CommandParser::class);
        
        $testCases = [
            'COMPRESS whatsapp' => ['type' => 'compress', 'mode' => 'whatsapp'],
            'CONVERT docx' => ['type' => 'convert', 'format' => 'docx'],
            'OCR text' => ['type' => 'ocr', 'output_format' => 'text'],
            'SUMMARIZE short' => ['type' => 'summarize', 'size' => 'short'],
            'TRANSLATE fr' => ['type' => 'translate', 'target_language' => 'fr'],
            'SECURE password' => ['type' => 'secure', 'security_type' => 'password'],
        ];

        foreach ($testCases as $command => $expected) {
            $result = $parser->parse($command);
            
            $this->assertNotNull($result, "Command '{$command}' should be parsed successfully");
            $this->assertEquals($expected['type'], $result['type']);
            
            // Check specific parameters based on operation type
            foreach ($expected as $key => $value) {
                if ($key !== 'type') {
                    $this->assertEquals($value, $result['parameters'][$key], 
                        "Parameter '{$key}' should be '{$value}' for command '{$command}'");
                }
            }
        }
    }

    public function test_invalid_commands_for_all_operations()
    {
        $invalidCommands = [
            'COMPRESS invalid_mode',
            'CONVERT invalid_format', 
            'SUMMARIZE invalid_size',
            'TRANSLATE invalid_lang',
            'SECURE invalid_type',
            'INVALID_OPERATION'
        ];

        foreach ($invalidCommands as $command) {
            $response = $this->postJson('/api/whatsapp/webhook', [
                'From' => 'whatsapp:+1234567890',
                'Body' => $command,
                'MediaUrl0' => 'https://example.com/test.pdf',
                'MediaContentType0' => 'application/pdf'
            ]);

            $this->assertEquals(200, $response->getStatusCode());
            
            // Should not create task jobs for invalid commands
            // (except for commands that have fallback defaults)
            if (!in_array($command, ['COMPRESS invalid_mode', 'CONVERT invalid_format'])) {
                $this->assertDatabaseMissing('task_jobs', [
                    'type' => strtolower(explode(' ', $command)[0])
                ]);
            }
        }
    }

    public function test_microservice_client_supports_all_operations()
    {
        $client = app(\App\Services\PdfMicroserviceClient::class);
        
        // Test that client has all required methods
        $this->assertTrue(method_exists($client, 'compressPdf'));
        $this->assertTrue(method_exists($client, 'convertPdf'));
        $this->assertTrue(method_exists($client, 'extractTextOcr'));
        $this->assertTrue(method_exists($client, 'summarizePdf'));
        $this->assertTrue(method_exists($client, 'translatePdf'));
        $this->assertTrue(method_exists($client, 'securePdf'));
        
        // Test helper methods
        $this->assertTrue(method_exists($client, 'healthCheck'));
        $this->assertTrue(method_exists($client, 'getSupportedOcrLanguages'));
        $this->assertTrue(method_exists($client, 'getSupportedTranslationLanguages'));
    }

    public function test_all_job_classes_implement_required_methods()
    {
        $jobClasses = [
            \App\Jobs\CompressPdfJob::class,
            \App\Jobs\ConvertPdfJob::class,
            \App\Jobs\OcrPdfJob::class,
            \App\Jobs\SummarizePdfJob::class,
            \App\Jobs\TranslatePdfJob::class,
            \App\Jobs\SecurePdfJob::class,
        ];

        foreach ($jobClasses as $jobClass) {
            $this->assertTrue(class_exists($jobClass));
            
            // Check that all jobs extend BasePdfJob
            $this->assertTrue(is_subclass_of($jobClass, \App\Jobs\BasePdfJob::class));
            
            // Create reflection to check required methods
            $reflection = new \ReflectionClass($jobClass);
            
            $this->assertTrue($reflection->hasMethod('callMicroservice'));
            $this->assertTrue($reflection->hasMethod('createPlaceholderContent'));
            $this->assertTrue($reflection->hasMethod('getPlaceholderFileExtension'));
            $this->assertTrue($reflection->hasMethod('getResultCaption'));
        }
    }
}
