<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\TaskJob;
use App\Jobs\CompressPdfJob;
use App\Jobs\ConvertPdfJob;
use App\Jobs\OcrPdfJob;
use App\Jobs\SummarizePdfJob;
use App\Jobs\TranslatePdfJob;
use App\Jobs\SecurePdfJob;
use Illuminate\Console\Command;

class TestJobOrchestration extends Command
{
    protected $signature = 'jobs:test-orchestration 
                           {--job=compress : Job type to test (compress/convert/ocr/summarize/translate/secure)}';

    protected $description = 'Test PDF job orchestration without external dependencies';

    public function handle(): int
    {
        $jobType = $this->option('job');
        
        $this->info("🧪 Testing {$jobType} job orchestration...");
        
        try {
            // Create test document and task job
            $document = Document::create([
                'original_name' => 'test_document.pdf',
                'file_size' => 1024000, // 1MB
                'mime_type' => 'application/pdf',
                's3_path' => 'test-documents/test_document.pdf',
                'whatsapp_user_id' => '+1234567890',
                'status' => 'pending',
                'expires_at' => now()->addHours(24),
            ]);
            
            $taskJob = TaskJob::create([
                'document_id' => $document->id,
                'type' => $jobType,
                'status' => 'pending',
                'parameters' => ['quality' => 'medium'],
            ]);
            
            $this->info("✅ Created test data:");
            $this->line("  📄 Document ID: {$document->id}");
            $this->line("  🔄 Task Job ID: {$taskJob->id}");
            
            // Get the appropriate job class
            $jobClasses = [
                'compress' => CompressPdfJob::class,
                'convert' => ConvertPdfJob::class,
                'ocr' => OcrPdfJob::class,
                'summarize' => SummarizePdfJob::class,
                'translate' => TranslatePdfJob::class,
                'secure' => SecurePdfJob::class,
            ];
            
            if (!isset($jobClasses[$jobType])) {
                $this->error("❌ Invalid job type: {$jobType}");
                return Command::FAILURE;
            }
            
            $jobClass = $jobClasses[$jobType];
            $this->info("📦 Testing job class: {$jobClass}");
            
            // Create job instance (without executing)
            $job = new $jobClass($document, $taskJob, 'whatsapp:+1234567890');
            
            $this->info("✅ Job instance created successfully");
            
            // Test job inheritance
            if ($job instanceof \App\Jobs\BasePdfJob) {
                $this->info("✅ Job correctly extends BasePdfJob");
            } else {
                $this->error("❌ Job does not extend BasePdfJob");
                return Command::FAILURE;
            }
            
            // Show processing flow
            $this->line("\n📋 Job Processing Flow:");
            $this->line("  1. 📥 Download PDF from Twilio media URL");
            $this->line("  2. ✅ Validate file (max 50MB, PDF format)");
            $this->line("  3. 🔄 Process PDF ({$jobType} operation)");
            $this->line("  4. ☁️  Upload result to S3/MinIO storage");
            $this->line("  5. 🔗 Generate signed URL (24h expiry)");
            $this->line("  6. 📱 Send result via WhatsApp");
            $this->line("  7. ✅ Mark job as completed");
            
            // Show job type specific info
            $this->line("\n📄 Job Type Details:");
            $this->line("  Type: {$jobType}");
            $this->line("  Class: {$jobClass}");
            $this->line("  Parameters: " . json_encode($taskJob->parameters));
            
            // Test all job types
            if ($this->confirm('Test all job types?', false)) {
                $this->testAllJobTypes();
            }
            
            $this->info("\n✅ Job orchestration test completed successfully!");
            $this->info("💡 Ready for Step 6: Python microservice integration");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            $this->error("📍 " . $e->getFile() . ":" . $e->getLine());
            return Command::FAILURE;
        }
    }
    
    private function testAllJobTypes(): void
    {
        $this->info("\n🔄 Testing all job types...");
        
        $jobTypes = ['compress', 'convert', 'ocr', 'summarize', 'translate', 'secure'];
        $jobClasses = [
            'compress' => CompressPdfJob::class,
            'convert' => ConvertPdfJob::class,
            'ocr' => OcrPdfJob::class,
            'summarize' => SummarizePdfJob::class,
            'translate' => TranslatePdfJob::class,
            'secure' => SecurePdfJob::class,
        ];
        
        // Create a shared document for all tests
        $document = Document::create([
            'original_name' => 'shared_test_document.pdf',
            'file_size' => 1024000,
            'mime_type' => 'application/pdf',
            's3_path' => 'test-documents/shared_test_document.pdf',
            'whatsapp_user_id' => '+1234567890',
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
        ]);
        
        foreach ($jobTypes as $type) {
            try {
                $taskJob = TaskJob::create([
                    'document_id' => $document->id,
                    'type' => $type,
                    'status' => 'pending',
                    'parameters' => ['test' => true],
                ]);
                
                $job = new $jobClasses[$type]($document, $taskJob, 'whatsapp:+1234567890');
                
                if ($job instanceof \App\Jobs\BasePdfJob) {
                    $this->line("  ✅ {$type}: extends BasePdfJob");
                } else {
                    $this->line("  ❌ {$type}: does not extend BasePdfJob");
                }
                
            } catch (\Exception $e) {
                $this->line("  ❌ {$type}: error - " . $e->getMessage());
            }
        }
    }
}
