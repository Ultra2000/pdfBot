<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\TaskJob;

class TranslatePdfJob extends BasePdfJob
{
    /**
     * Call the microservice for PDF translation
     */
    protected function callMicroservice(): array
    {
        $pdfClient = app(\App\Services\PdfMicroserviceClient::class);
        
        $targetLanguage = $this->taskJob->parameters['target_language'] ?? 'en';
        
        return $pdfClient->translatePdf($this->document->s3_path, $targetLanguage);
    }
    
    /**
     * Create placeholder content for translation
     */
    protected function createPlaceholderContent(): string
    {
        $targetLanguage = $this->taskJob->parameters['target_language'] ?? 'en';
        $originalName = $this->document->original_name;
        
        $languageNames = [
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português'
        ];
        
        $languageName = $languageNames[$targetLanguage] ?? $targetLanguage;
        
        return "🌍 PDF Translation Result (Placeholder)\n\n" .
               "Original: {$originalName}\n" .
               "Target Language: {$languageName} ({$targetLanguage})\n" .
               "Status: ✅ Translated successfully\n" .
               "Words translated: ~1,200 (estimated)\n\n" .
               "Sample translated content:\n" .
               "Lorem ipsum has been translated to the target language...\n\n" .
               "Note: This is a placeholder result. " .
               "Actual translation will be implemented with Python microservice + Translation API in Step 6-7.";
    }
    
    /**
     * Get placeholder file extension
     */
    protected function getPlaceholderFileExtension(): string
    {
        return 'pdf';
    }

    /**
     * Get result caption for WhatsApp message
     */
    protected function getResultCaption(): string
    {
        $targetLanguage = $this->taskJob->parameters['target_language'] ?? 'en';
        
        $languageNames = [
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português'
        ];
        
        $languageName = $languageNames[$targetLanguage] ?? $targetLanguage;
        
        return "✅ *PDF Traduit*\n\n" .
               "Langue: {$languageName}\n" .
               "Télécharger le fichier ci-dessus\n\n" .
               "⏱️ Temps de traitement: " . ($this->taskJob->processing_time_seconds ?? 0) . "s";
    }
}
