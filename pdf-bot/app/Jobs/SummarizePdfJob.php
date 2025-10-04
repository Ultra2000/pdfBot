<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\TaskJob;

class SummarizePdfJob extends BasePdfJob
{
    /**
     * Call PDF microservice for summarization
     */
    protected function callMicroservice(): array
    {
        $options = [
            'length' => $this->taskJob->parameters['length'] ?? 'medium',
            'language' => $this->taskJob->parameters['language'] ?? 'en'
        ];

        return $this->microserviceClient->summarizePdf($this->document->s3_path, $options);
    }

    /**
     * Create placeholder result when microservice is unavailable
     */
    protected function createPlaceholderContent(): string
    {
        $size = $this->taskJob->parameters['size'] ?? 'short';
        $originalName = $this->document->original_name;
        
        $summary = match($size) {
            'short' => "Résumé court du document PDF. Les points clés sont présentés de manière concise.",
            'medium' => "Résumé moyen du document PDF avec plus de détails. Les sections principales sont analysées et les idées importantes sont développées avec des explications supplémentaires.",
            'long' => "Résumé détaillé du document PDF avec une analyse approfondie. Chaque section est examinée en profondeur, les arguments sont développés, les exemples sont expliqués, et les conclusions sont présentées avec leur contexte complet.",
            default => "Résumé du document PDF."
        };
        
        return "📝 PDF Summary Result (Placeholder)\n\n" .
               "Original: {$originalName}\n" .
               "Summary Size: {$size}\n" .
               "Status: ✅ Summarized successfully\n\n" .
               "RÉSUMÉ:\n" .
               "{$summary}\n\n" .
               "Note: This is a placeholder result. " .
               "Actual summarization will be implemented with Python microservice + LLM.";
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
        $size = $this->taskJob->parameters['size'] ?? 'short';
        
        return "✅ *PDF Résumé*\n\n" .
               "Taille: {$size}\n" .
               "Télécharger le résumé ci-dessus\n\n" .
               "⏱️ Temps de traitement: " . ($this->taskJob->processing_time_seconds ?? 0) . "s";
    }
}
