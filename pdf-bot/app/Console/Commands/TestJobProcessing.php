<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\TaskJob;
use App\Jobs\CompressPdfJob;
use App\Support\CommandParser;
use Illuminate\Console\Command;

class TestJobProcessing extends Command
{
    protected $signature = 'jobs:test-processing 
                           {--type=compress : Job type to test (compress/convert/ocr/summarize/translate/secure)}
                           {--mode=whatsapp : Mode parameter for the job}';

    protected $description = 'Test PDF job processing with mock data';

    public function handle(): int
    {
        $type = $this->option('type');
        $mode = $this->option('mode');

        $this->info("🧪 Testing {$type} job processing...");

        try {
            // Create mock document
            $document = Document::create([
                'original_name' => 'test_document.pdf',
                'whatsapp_user_id' => '+1234567890',
                'status' => 'pending',
                'metadata' => [
                    'media_url' => 'https://example.com/test.pdf',
                    'command' => strtoupper($type) . ' ' . $mode,
                    'from' => 'whatsapp:+1234567890',
                    'test_mode' => true
                ],
                'expires_at' => now()->addHours(24)
            ]);

            // Parse command to get correct job class and parameters
            $parser = new CommandParser();
            $parsed = $parser->parse(strtoupper($type) . ' ' . $mode);

            if (!$parsed) {
                $this->error("Invalid command: {$type} {$mode}");
                return Command::FAILURE;
            }

            // Create task job
            $taskJob = TaskJob::create([
                'document_id' => $document->id,
                'type' => $parsed['type'],
                'status' => 'pending',
                'parameters' => $parsed['parameters']
            ]);

            $this->info("Created document ID: {$document->id}");
            $this->info("Created task job ID: {$taskJob->id}");

            // Dispatch the job
            $jobClass = $parsed['job_class'];
            $this->info("Dispatching job: {$jobClass}");

            // For testing, we'll run synchronously
            $job = new $jobClass($document, $taskJob, 'whatsapp:+1234567890');
            
            $this->warn("Note: This will attempt to download from the media URL and may fail in test mode.");
            $this->warn("The job will demonstrate the full processing flow.");

            if ($this->confirm('Do you want to proceed with job execution?')) {
                $job->handle();
                
                // Refresh models to see updated data
                $document->refresh();
                $taskJob->refresh();
                
                $this->info("Job execution completed!");
                $this->line("Document status: {$document->status}");
                $this->line("Task job status: {$taskJob->status}");
                
                if ($taskJob->error_message) {
                    $this->error("Error: {$taskJob->error_message}");
                }
                
                if ($taskJob->processing_time_seconds) {
                    $this->line("Processing time: {$taskJob->processing_time_seconds} seconds");
                }
                
                return Command::SUCCESS;
            } else {
                $this->info("Job execution cancelled.");
                return Command::SUCCESS;
            }

        } catch (\Exception $e) {
            $this->error("Test failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
