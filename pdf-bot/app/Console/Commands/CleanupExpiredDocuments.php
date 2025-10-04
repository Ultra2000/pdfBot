<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\StorageService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupExpiredDocuments extends Command
{
    protected $signature = 'documents:cleanup-expired 
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--force : Force deletion without confirmation}';

    protected $description = 'Clean up expired documents from storage and database';

    protected StorageService $storageService;

    public function __construct(StorageService $storageService)
    {
        parent::__construct();
        $this->storageService = $storageService;
    }

    public function handle(): int
    {
        $this->info('🧹 Starting cleanup of expired documents...');

        // Find expired documents
        $expiredDocuments = Document::where('expires_at', '<', Carbon::now())
            ->orderBy('expires_at')
            ->get();

        if ($expiredDocuments->isEmpty()) {
            $this->info('✅ No expired documents found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiredDocuments->count()} expired documents.");

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No files will be deleted');
            $this->showDocumentsTable($expiredDocuments);
            return Command::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Do you want to proceed with deletion?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $deletedCount = 0;
        $errorCount = 0;
        $freedBytes = 0;

        foreach ($expiredDocuments as $document) {
            try {
                $this->info("Deleting document: {$document->original_name}");

                // Delete from storage
                $storageDeleted = true;
                if ($document->s3_path && $this->storageService->fileExists($document->s3_path)) {
                    $freedBytes += $document->file_size ?? 0;
                    $storageDeleted = $this->storageService->deleteFile($document->s3_path);
                }

                // Delete output file if exists
                if ($document->s3_output_path && $this->storageService->fileExists($document->s3_output_path)) {
                    $freedBytes += $document->output_file_size ?? 0;
                    $this->storageService->deleteFile($document->s3_output_path);
                }

                if ($storageDeleted) {
                    // Delete database record
                    $document->delete();
                    $deletedCount++;
                    $this->line("  ✅ Deleted: {$document->original_name}");
                } else {
                    $this->warn("  ⚠️  Failed to delete storage file for: {$document->original_name}");
                    $errorCount++;
                }

            } catch (\Exception $e) {
                $this->error("  ❌ Error deleting {$document->original_name}: " . $e->getMessage());
                $errorCount++;
            }
        }

        // Summary
        $this->newLine();
        $this->info("📊 Cleanup Summary:");
        $this->line("  ✅ Successfully deleted: {$deletedCount} documents");
        
        if ($errorCount > 0) {
            $this->warn("  ⚠️  Errors encountered: {$errorCount} documents");
        }
        
        if ($freedBytes > 0) {
            $freedMB = round($freedBytes / 1024 / 1024, 2);
            $this->line("  💾 Storage freed: {$freedMB} MB");
        }

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function showDocumentsTable($documents): void
    {
        $tableData = $documents->map(function ($doc) {
            return [
                'ID' => $doc->id,
                'Name' => substr($doc->original_name, 0, 30) . (strlen($doc->original_name) > 30 ? '...' : ''),
                'Size' => $doc->file_size ? round($doc->file_size / 1024, 1) . ' KB' : 'Unknown',
                'Expired' => $doc->expires_at->diffForHumans(),
                'Status' => $doc->status,
            ];
        })->toArray();

        $this->table(
            ['ID', 'Name', 'Size', 'Expired', 'Status'],
            $tableData
        );
    }
}
