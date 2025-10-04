<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Models\Document;
use App\Models\TaskJob;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Twilio configuration for testing
        config([
            'services.twilio.sid' => 'test_sid',
            'services.twilio.auth_token' => 'test_token',
            'services.twilio.whatsapp_number' => 'whatsapp:+14155238886'
        ]);
    }

    public function test_webhook_route_exists()
    {
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'HELP'
        ]);

        // Should not be 404
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_help_command_response()
    {
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'HELP'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_status_command_response()
    {
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'STATUS'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_pdf_message_creates_document_and_job()
    {
        // Disable queue processing for this test
        \Queue::fake();
        
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'COMPRESS whatsapp',
            'MediaUrl0' => 'https://example.com/test.pdf',
            'MediaContentType0' => 'application/pdf'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertDatabaseHas('documents', [
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('task_jobs', [
            'type' => 'compress',
            'status' => 'pending'
        ]);
    }

    public function test_invalid_media_type_shows_help()
    {
        $response = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+1234567890',
            'Body' => 'COMPRESS',
            'MediaUrl0' => 'https://example.com/test.jpg',
            'MediaContentType0' => 'image/jpeg'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
