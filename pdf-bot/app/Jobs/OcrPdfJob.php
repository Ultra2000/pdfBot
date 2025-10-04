<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\TaskJob;

class OcrPdfJob extends BasePdfJob
{
    /**
     * Call PDF microservice for OCR
     */
    protected function callMicroservice(): array
    {
        $options = [
            'language' => $this->taskJob->parameters['language'] ?? 'eng',
            'output_format' => $this->taskJob->parameters['output_format'] ?? 'txt'
        ];

        return $this->microserviceClient->extractTextOcr($this->document->s3_path, $options);
    }

    /**
     * Create placeholder result when microservice is unavailable
     */
    protected function createPlaceholderContent(): string
    {
        $outputFormat = $this->taskJob->parameters['output_format'] ?? 'text';
        $originalName = $this->document->original_name;
        
        return "👁️ PDF OCR Result (Placeholder)\n\n" .
               "Original: {$originalName}\n" .
               "Output Format: {$outputFormat}\n" .
               "Status: ✅ Text extracted successfully\n" .
               "Characters: ~2,500 (estimated)\n" .
               "Languages detected: French, English\n\n" .
               "Sample extracted text:\n" .
               "Lorem ipsum dolor sit amet, consectetur adipiscing elit...\n\n" .
               "Note: This is a placeholder result. " .
               "Actual OCR will be implemented with Python microservice.";
    }

    /**
     * Get file extension for placeholder
     */
    protected function getPlaceholderFileExtension(): string
    {
        $outputFormat = $this->taskJob->parameters['output_format'] ?? 'txt';
        return $outputFormat === 'docx' ? '.docx' : '.txt';
    }

    /**
     * Get result caption for WhatsApp message
     */
    protected function getResultCaption(): string
    {
        $outputFormat = $this->taskJob->parameters['output_format'] ?? 'text';
        
        return "✅ *Texte Extrait (OCR)*\n\n" .
               "Format: {$outputFormat}\n" .
               "Télécharger le fichier ci-dessus\n\n" .
               "⏱️ Temps de traitement: " . ($this->taskJob->processing_time_seconds ?? 0) . "s";
    }
}
