<?php

namespace Tests\Unit;

use App\Jobs\CompressPdfJob;
use App\Models\Document;
use App\Models\TaskJob;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_compress_pdf_job_can_be_instantiated()
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

        $job = new CompressPdfJob($document, $taskJob, 'whatsapp:+1234567890');

        $this->assertInstanceOf(CompressPdfJob::class, $job);
    }

    public function test_compress_pdf_job_execution()
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

        $job = new CompressPdfJob($document, $taskJob, 'whatsapp:+1234567890');
        $job->handle();

        // Vérifier que le job a été marqué comme completed (placeholder behavior)
        $taskJob->refresh();
        $this->assertEquals('completed', $taskJob->status);
        $this->assertNotNull($taskJob->completed_at);
    }
}
