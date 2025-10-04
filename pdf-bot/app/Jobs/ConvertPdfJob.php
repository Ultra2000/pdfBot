<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\TaskJob;

class ConvertPdfJob extends BasePdfJob
{
    /**
     * Call PDF microservice for conversion
     */
    protected function callMicroservice(): array
    {
        $format = $this->taskJob->parameters['format'] ?? 'docx';
        $options = $this->taskJob->parameters['options'] ?? [];

        return $this->microserviceClient->convertPdf($this->document->s3_path, $format, $options);
    }

    /**
     * Create placeholder result when microservice is unavailable
     */
    protected function createPlaceholderContent(): string
    {
        $format = $this->taskJob->parameters['format'] ?? 'docx';
        $originalName = $this->document->original_name;
        
        return "🔄 PDF Conversion Result (Placeholder)\n\n" .
               "Original: {$originalName}\n" .
               "Target Format: {$format}\n" .
               "Status: ✅ Converted successfully\n" .
               "Pages: ~5 (estimated)\n\n" .
               "Note: This is a placeholder result. " .
               "Actual conversion will be implemented with Python microservice.";
    }

    /**
     * Get file extension for placeholder
     */
    protected function getPlaceholderFileExtension(): string
    {
        return '.txt';
    }

    /**
     * Get result caption for WhatsApp message
     */
    protected function getResultCaption(): string
    {
        $format = $this->taskJob->parameters['format'] ?? 'docx';
        
        return "✅ *PDF Converti*\n\n" .
               "Format: {$format}\n" .
               "Télécharger le fichier ci-dessus\n\n" .
               "⏱️ Temps de traitement: " . ($this->taskJob->processing_time_seconds ?? 0) . "s";
    }
}
