<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\TaskJob;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer quelques documents de test
        $document1 = Document::create([
            'original_name' => 'rapport_mensuel.pdf',
            'status' => 'completed',
            'file_size' => 2048000,
            'mime_type' => 'application/pdf',
            'whatsapp_user_id' => '+33123456789',
            'expires_at' => now()->addDays(1),
        ]);

        $document2 = Document::create([
            'original_name' => 'presentation.pdf',
            'status' => 'processing',
            'file_size' => 5120000,
            'mime_type' => 'application/pdf',
            'whatsapp_user_id' => '+33987654321',
            'expires_at' => now()->addDays(1),
        ]);

        $document3 = Document::create([
            'original_name' => 'contrat.pdf',
            'status' => 'failed',
            'file_size' => 1024000,
            'mime_type' => 'application/pdf',
            'whatsapp_user_id' => '+33555666777',
            'expires_at' => now()->addDays(1),
        ]);

        // Créer quelques jobs de test
        TaskJob::create([
            'document_id' => $document1->id,
            'type' => 'compress',
            'status' => 'completed',
            'parameters' => ['mode' => 'whatsapp'],
            'processing_time_seconds' => 45,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()->subMinutes(4),
        ]);

        TaskJob::create([
            'document_id' => $document2->id,
            'type' => 'convert',
            'status' => 'running',
            'parameters' => ['format' => 'docx'],
            'started_at' => now()->subMinutes(2),
        ]);

        TaskJob::create([
            'document_id' => $document3->id,
            'type' => 'ocr',
            'status' => 'failed',
            'parameters' => ['language' => 'fr'],
            'error_message' => 'Fichier corrompu',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(9),
        ]);

        TaskJob::create([
            'document_id' => $document1->id,
            'type' => 'translate',
            'status' => 'pending',
            'parameters' => ['target_language' => 'en'],
        ]);

        $this->command->info('Données de test créées avec succès !');
        $this->command->info('- 3 documents');
        $this->command->info('- 4 jobs de traitement');
    }
}
