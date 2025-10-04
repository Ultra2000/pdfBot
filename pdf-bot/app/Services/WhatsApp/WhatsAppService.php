<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\MessagingProvider;
use App\Services\WhatsApp\Providers\TwilioProvider;
use App\Services\WhatsApp\Providers\MetaProvider;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private MessagingProvider $provider;

    public function __construct()
    {
        $this->provider = $this->createProvider();
    }

    /**
     * Create messaging provider based on configuration
     */
    private function createProvider(): MessagingProvider
    {
        $providerName = config('messaging.default', 'twilio');

        return match ($providerName) {
            'meta' => new MetaProvider(),
            'twilio' => new TwilioProvider(),
            default => throw new \InvalidArgumentException("Unsupported messaging provider: {$providerName}")
        };
    }

    /**
     * Send text message
     */
    public function sendText(string $to, string $message): bool
    {
        return $this->provider->sendText($to, $message);
    }

    /**
     * Send document
     */
    public function sendDocument(string $to, string $documentUrl, string $caption = '', string $filename = ''): bool
    {
        return $this->provider->sendDocument($to, $documentUrl, $caption, $filename);
    }

    /**
     * Send image
     */
    public function sendImage(string $to, string $imageUrl, string $caption = ''): bool
    {
        return $this->provider->sendImage($to, $imageUrl, $caption);
    }

    /**
     * Get current provider name
     */
    public function getProviderName(): string
    {
        return $this->provider->getProviderName();
    }

    /**
     * Handle incoming text message
     */
    public function handleTextMessage(string $from, string $text): void
    {
        Log::info('Processing text message', [
            'from' => $from,
            'text' => $text,
            'provider' => $this->getProviderName()
        ]);

        // Check if this is a menu selection (1-6)
        if (preg_match('/^[1-6]$/', trim($text))) {
            $this->handleMenuSelection($from, (int)trim($text));
            return;
        }

        // Send menu for any other text
        $this->sendMainMenu($from);
    }

    /**
     * Handle incoming media message
     */
    public function handleMediaMessage(string $from, string $mediaUrl, string $mimeType, string $filename = ''): void
    {
        Log::info('Processing media message', [
            'from' => $from,
            'media_url' => $mediaUrl,
            'mime_type' => $mimeType,
            'filename' => $filename,
            'provider' => $this->getProviderName()
        ]);

        // Check if it's a PDF
        if ($mimeType === 'application/pdf') {
            $this->handlePdfUpload($from, $mediaUrl, $filename);
        } else {
            $this->sendText($from, "❌ Format non supporté. Veuillez envoyer un fichier PDF.");
        }
    }

    /**
     * Handle PDF upload
     */
    private function handlePdfUpload(string $from, string $mediaUrl, string $filename): void
    {
        // Store document and send menu
        $document = \App\Models\Document::create([
            'whatsapp_user' => $from,
            'original_filename' => $filename ?: 'document.pdf',
            'media_url' => $mediaUrl,
            'mime_type' => 'application/pdf',
            'status' => 'received',
        ]);

        Log::info('PDF received - menu sent', [
            'whatsapp_user' => $from,
            'media_url' => $mediaUrl,
            'document_id' => $document->id
        ]);

        $this->sendPdfMenu($from, $document->id);
    }

    /**
     * Send main menu
     */
    private function sendMainMenu(string $to): void
    {
        $menu = "🤖 *Bot PDF* - Bienvenue !\n\n";
        $menu .= "Envoyez-moi un fichier PDF et je vous proposerai plusieurs options de traitement :\n\n";
        $menu .= "📄 *Fonctionnalités disponibles :*\n";
        $menu .= "• Compression\n";
        $menu .= "• Conversion (Word, Image)\n";
        $menu .= "• OCR (Reconnaissance de texte)\n";
        $menu .= "• Résumé automatique\n";
        $menu .= "• Traduction\n";
        $menu .= "• Sécurisation\n\n";
        $menu .= "📎 *Envoyez votre PDF pour commencer !*";

        $this->sendText($to, $menu);
    }

    /**
     * Send PDF processing menu
     */
    private function sendPdfMenu(string $to, int $documentId): void
    {
        $menu = "🤖 *PDF reçu !* Choisissez votre action :\n\n";
        $menu .= "1️⃣ *Compresser* le PDF\n";
        $menu .= "2️⃣ *Convertir* (PDF → Word/Image)\n";
        $menu .= "3️⃣ *OCR* (Extraire le texte)\n";
        $menu .= "4️⃣ *Résumer* le contenu\n";
        $menu .= "5️⃣ *Traduire* le texte\n";
        $menu .= "6️⃣ *Sécuriser* avec mot de passe\n\n";
        $menu .= "💬 *Tapez le numéro de votre choix (1-6)*";

        // Store document ID in session or cache for later use
        cache()->put("document_{$to}", $documentId, 3600); // 1 hour

        $this->sendText($to, $menu);
    }

    /**
     * Handle menu selection
     */
    private function handleMenuSelection(string $from, int $selection): void
    {
        $documentId = cache()->get("document_{$from}");
        
        if (!$documentId) {
            $this->sendText($from, "❌ Session expirée. Veuillez renvoyer votre PDF.");
            return;
        }

        $document = \App\Models\Document::find($documentId);
        if (!$document) {
            $this->sendText($from, "❌ Document non trouvé. Veuillez renvoyer votre PDF.");
            return;
        }

        $actionTypes = [
            1 => 'compress',
            2 => 'convert',
            3 => 'ocr',
            4 => 'summarize',
            5 => 'translate',
            6 => 'secure'
        ];

        $actionType = $actionTypes[$selection] ?? null;
        if (!$actionType) {
            $this->sendText($from, "❌ Choix invalide. Veuillez choisir un numéro entre 1 et 6.");
            return;
        }

        $this->processSelection($from, $document, $actionType, $selection);
    }

    /**
     * Process menu selection
     */
    private function processSelection(string $from, \App\Models\Document $document, string $actionType, int $selection): void
    {
        // Create task job
        $taskJob = \App\Models\TaskJob::create([
            'document_id' => $document->id,
            'type' => $actionType,
            'status' => 'pending',
            'whatsapp_user' => $from,
            'selection_method' => 'menu',
            'provider' => $this->getProviderName(),
        ]);

        // Send confirmation
        $actionNames = [
            1 => 'Compression',
            2 => 'Conversion',
            3 => 'OCR',
            4 => 'Résumé',
            5 => 'Traduction',
            6 => 'Sécurisation'
        ];

        $actionName = $actionNames[$selection];
        $this->sendText($from, "✅ *{$actionName}* en cours...\n\n⏳ Traitement de votre PDF avec le microservice Python.\n\n📱 Vous recevrez le résultat dans quelques instants !");

        // Dispatch appropriate job
        $jobClass = "App\\Jobs\\" . ucfirst($actionType) . "PdfJob";
        if (class_exists($jobClass)) {
            dispatch(new $jobClass($taskJob));
        } else {
            Log::error("Job class not found: {$jobClass}");
            $this->sendText($from, "❌ Erreur: Type de traitement non disponible.");
        }

        Log::info('PDF processing job dispatched via menu', [
            'document_id' => $document->id,
            'task_job_id' => $taskJob->id,
            'type' => $actionType,
            'whatsapp_user' => $from,
            'selection_method' => 'menu',
            'provider' => $this->getProviderName()
        ]);
    }
}
