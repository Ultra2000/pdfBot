<?php

namespace Tests\Unit;

use App\Models\TaskJob;
use App\Models\Document;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskJobModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_job_can_be_created()
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

        $this->assertDatabaseHas('task_jobs', [
            'document_id' => $document->id,
            'type' => 'compress',
            'status' => 'pending'
        ]);
    }

    public function test_task_job_scopes()
    {
        $document = Document::create([
            'original_name' => 'test.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending'
        ]);

        TaskJob::create([
            'document_id' => $document->id,
            'type' => 'compress',
            'status' => 'pending',
            'parameters' => []
        ]);

        TaskJob::create([
            'document_id' => $document->id,
            'type' => 'convert',
            'status' => 'running',
            'parameters' => []
        ]);

        TaskJob::create([
            'document_id' => $document->id,
            'type' => 'ocr',
            'status' => 'completed',
            'parameters' => []
        ]);

        TaskJob::create([
            'document_id' => $document->id,
            'type' => 'translate',
            'status' => 'failed',
            'parameters' => []
        ]);

        $this->assertEquals(1, TaskJob::pending()->count());
        $this->assertEquals(1, TaskJob::running()->count());
        $this->assertEquals(1, TaskJob::completed()->count());
        $this->assertEquals(1, TaskJob::failed()->count());
    }

    public function test_task_job_belongs_to_document()
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
            'parameters' => []
        ]);

        $this->assertEquals($document->id, $taskJob->document->id);
    }
}
