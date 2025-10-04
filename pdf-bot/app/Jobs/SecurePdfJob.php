<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\TaskJob;

class SecurePdfJob extends BasePdfJob
{
    /**
     * Call the microservice for PDF security
     */
    protected function callMicroservice(): array
    {
        $pdfClient = app(\App\Services\PdfMicroserviceClient::class);
        
        $securityType = $this->taskJob->parameters['security_type'] ?? 'password';
        $password = $this->taskJob->parameters['password'] ?? null;
        $watermarkText = $this->taskJob->parameters['watermark_text'] ?? null;
        
        return $pdfClient->securePdf(
            $this->document->s3_path, 
            $securityType, 
            $password, 
            $watermarkText
        );
    }
    
    /**
     * Create placeholder content for security
     */
    protected function createPlaceholderContent(): string
    {
        $securityType = $this->taskJob->parameters['security_type'] ?? 'password';
        $password = $this->taskJob->parameters['password'] ?? null;
        $originalName = $this->document->original_name;
        
        $securityInfo = match($securityType) {
            'password' => "Protection par mot de passe appliquée.\nMot de passe: {$password}",
            'watermark' => "Filigrane (watermark) appliqué au document.",
            'both' => "Protection par mot de passe ET filigrane appliqués.\nMot de passe: {$password}",
            default => "Sécurisation appliquée."
        };
        
        return "🔒 PDF Security Result (Placeholder)\n\n" .
               "Original: {$originalName}\n" .
               "Security Type: {$securityType}\n" .
               "Status: ✅ Secured successfully\n\n" .
               "SÉCURITÉ APPLIQUÉE:\n" .
               "{$securityInfo}\n\n" .
               "⚠️ IMPORTANT: Conservez ces informations en sécurité!\n\n" .
               "Note: This is a placeholder result. " .
               "Actual security features will be implemented with Python microservice in Step 6-7.";
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
        $securityType = $this->taskJob->parameters['security_type'] ?? 'password';
        $password = $this->taskJob->parameters['password'] ?? null;
        
        $caption = "✅ *PDF Sécurisé*\n\n" .
                   "Type: {$securityType}\n";
        
        if ($password && in_array($securityType, ['password', 'both'])) {
            $caption .= "🔑 Mot de passe: `{$password}`\n";
        }
        
        $caption .= "Télécharger le fichier ci-dessus\n\n" .
                    "⏱️ Temps de traitement: " . ($this->taskJob->processing_time_seconds ?? 0) . "s";
        
        return $caption;
    }
}
