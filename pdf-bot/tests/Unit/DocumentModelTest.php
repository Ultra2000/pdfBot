<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Models\TaskJob;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_can_be_created()
    {
        $document = Document::create([
            'original_name' => 'test.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending',
            'metadata' => ['test' => 'data'],
            'expires_at' => now()->addHours(24)
        ]);

        $this->assertDatabaseHas('documents', [
            'original_name' => 'test.pdf',
            'whatsapp_user_id' => '+1234567890'
        ]);
    }

    public function test_document_has_task_jobs_relationship()
    {
        $document = Document::create([
            'original_name' => 'test.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending'
        ]);

        $taskJob = TaskJob::create([
            'document_id' => $document->id,
            'type' => 'compress',
            'status' => 'pending',
            'parameters' => ['mode' => 'whatsapp']
        ]);

        $this->assertTrue($document->taskJobs->contains($taskJob));
    }

    public function test_document_expiration_check()
    {
        $expiredDoc = Document::create([
            'original_name' => 'expired.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'completed',
            'expires_at' => now()->subHour()
        ]);

        $activeDoc = Document::create([
            'original_name' => 'active.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending',
            'expires_at' => now()->addHour()
        ]);

        $this->assertTrue($expiredDoc->isExpired());
        $this->assertFalse($activeDoc->isExpired());
    }

    public function test_document_scopes()
    {
        Document::create([
            'original_name' => 'expired.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'completed',
            'expires_at' => now()->subHour()
        ]);

        Document::create([
            'original_name' => 'active.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending',
            'expires_at' => now()->addHour()
        ]);

        $expiredCount = Document::expired()->count();
        $activeCount = Document::active()->count();

        $this->assertEquals(1, $expiredCount);
        $this->assertEquals(1, $activeCount);
    }
}
