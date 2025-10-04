<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\TaskJob;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class Step8ValidationTest extends TestCase
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

    public function test_step8_webhook_creates_compress_job()
    {
        // Disable queue processing for this test
        \Queue::fake();
        
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'COMPRESS whatsapp',
            'MediaUrl0' => 'https://example.com/test.pdf',
            'MediaContentType0' => 'application/pdf'
        ]);

        // Assert webhook response
        $this->assertEquals(200, $response->getStatusCode());
        
        // Assert database records created
        $this->assertDatabaseHas('documents', [
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('task_jobs', [
            'type' => 'compress',
            'status' => 'pending'
        ]);

        // Get the created task job
        $taskJob = TaskJob::where('type', 'compress')->first();
        $this->assertNotNull($taskJob);
        $this->assertEquals('whatsapp', $taskJob->parameters['mode']);
        
        // Verify the job was dispatched
        \Queue::assertPushed(\App\Jobs\CompressPdfJob::class);
    }

    public function test_step8_different_compress_modes()
    {
        \Queue::fake();
        
        $modes = [
            'whatsapp' => 'whatsapp',
            'impression' => 'impression', 
            'équilibré' => 'équilibré',
            '' => 'whatsapp' // default
        ];

        foreach ($modes as $input => $expected) {
            $body = $input ? "COMPRESS {$input}" : "COMPRESS";
            
            $response = $this->postJson('/api/whatsapp/webhook', [
                'From' => "whatsapp:+123456789{$input}",
                'Body' => $body,
                'MediaUrl0' => 'https://example.com/test.pdf',
                'MediaContentType0' => 'application/pdf'
            ]);

            $this->assertEquals(200, $response->getStatusCode());
            
            $this->assertDatabaseHas('task_jobs', [
                'type' => 'compress',
                'parameters->mode' => $expected
            ]);
        }
    }

    public function test_step8_invalid_command_handling()
    {
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'INVALID_COMMAND',
            'MediaUrl0' => 'https://example.com/test.pdf',
            'MediaContentType0' => 'application/pdf'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        
        // Should not create any jobs for invalid commands
        $this->assertDatabaseMissing('task_jobs', [
            'type' => 'compress'
        ]);
    }

    public function test_step8_no_pdf_handling()
    {
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'COMPRESS whatsapp'
            // No MediaUrl0 or MediaContentType0
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        
        // Should not create document/job without PDF
        $this->assertDatabaseMissing('documents', [
            'whatsapp_user_id' => '+1234567890'
        ]);
    }

    public function test_step8_classes_exist()
    {
        // Test that all required classes exist and are properly configured
        $this->assertTrue(class_exists(\App\Http\Controllers\Api\WhatsAppWebhookController::class));
        $this->assertTrue(class_exists(\App\Support\CommandParser::class));
        $this->assertTrue(class_exists(\App\Jobs\CompressPdfJob::class));
        $this->assertTrue(class_exists(\App\Services\PdfMicroserviceClient::class));
        $this->assertTrue(class_exists(\App\Models\Document::class));
        $this->assertTrue(class_exists(\App\Models\TaskJob::class));
    }

    public function test_step8_command_parser()
    {
        $parser = app(\App\Support\CommandParser::class);
        
        // Test valid COMPRESS commands
        $result = $parser->parse('COMPRESS whatsapp');
        $this->assertNotNull($result);
        $this->assertEquals('compress', $result['type']);
        $this->assertEquals(\App\Jobs\CompressPdfJob::class, $result['job_class']);
        $this->assertEquals('whatsapp', $result['parameters']['mode']);
        
        $result = $parser->parse('COMPRESS impression');
        $this->assertEquals('impression', $result['parameters']['mode']);
        
        // Test invalid command
        $result = $parser->parse('INVALID_COMMAND');
        $this->assertNull($result);
    }
}
