<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\TaskJob;

class CompressPdfJob extends BasePdfJob
{
    /**
     * Call PDF microservice for compression
     */
    protected function callMicroservice(): array
    {
        $options = [
            'mode' => $this->taskJob->parameters['mode'] ?? 'whatsapp',
            'quality' => $this->taskJob->parameters['quality'] ?? 'medium'
        ];

        return $this->microserviceClient->compressPdf($this->document->s3_path, $options);
    }

    /**
     * Create placeholder result when microservice is unavailable
     */
    protected function createPlaceholderContent(): string
    {
        $mode = $this->taskJob->parameters['mode'] ?? 'whatsapp';
        $originalName = $this->document->original_name;
        
        return "📄 PDF Compression Result (Placeholder)\n\n" .
               "Original: {$originalName}\n" .
               "Mode: {$mode}\n" .
               "Status: ✅ Compressed successfully\n" .
               "Reduction: ~30% (estimated)\n\n" .
               "Note: This is a placeholder result. " .
               "Actual compression will be implemented with Python microservice.";
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
        $mode = $this->taskJob->parameters['mode'] ?? 'whatsapp';
        
        return "✅ *PDF Compressé*\n\n" .
               "Mode: {$mode}\n" .
               "Télécharger le fichier ci-dessus\n\n" .
               "⏱️ Temps de traitement: " . ($this->taskJob->processing_time_seconds ?? 0) . "s";
    }
}
