<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\TaskJob;
use App\Support\CommandParser;
use App\Services\Messaging\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    protected TwilioService $twilioService;
    protected CommandParser $commandParser;

    public function __construct(TwilioService $twilioService, CommandParser $commandParser)
    {
        $this->twilioService = $twilioService;
        $this->commandParser = $commandParser;
    }

    public function handle(Request $request): Response
    {
        try {
            Log::info('WhatsApp webhook received', ['payload' => $request->all()]);

            // Validate Twilio signature for security
            if (!$this->validateTwilioSignature($request)) {
                Log::warning('Invalid Twilio signature', ['url' => $request->fullUrl()]);
                return response('Forbidden', 403);
            }

            $from = $request->input('From');
            $body = $request->input('Body');
            $mediaUrl = $request->input('MediaUrl0');
            $mediaContentType = $request->input('MediaContentType0');

            // Extract WhatsApp user ID (remove whatsapp: prefix)
            $whatsappUserId = str_replace('whatsapp:', '', $from);

            // Handle new user welcome (first message)
            if ($this->isFirstInteraction($whatsappUserId)) {
                return $this->sendWelcomeMessage($from);
            }

            // Handle media (PDF) messages
            if ($mediaUrl && $this->isPdf($mediaContentType)) {
                return $this->handlePdfMessage($whatsappUserId, $from, $body, $mediaUrl);
            }

            // Handle menu selections (numbers 1-6 and submenu codes)
            if ($body && preg_match('/^([1-6]|2[1-3]|4[1-3]|5[1-5])$/', trim($body))) {
                return $this->handleMenuSelection($whatsappUserId, $from, $body);
            }

            // Handle text commands and help
            if ($body) {
                return $this->handleTextCommand($whatsappUserId, $from, $body);
            }

            // Send help message for unsupported messages
            $this->twilioService->sendText($from, $this->getHelpMessage());
            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response('Internal Server Error', 500);
        }
    }

    protected function validateTwilioSignature(Request $request): bool
    {
        // In production, implement Twilio signature validation
        // For MVP, we'll skip this validation
        return true;
    }

    protected function isPdf(string $contentType = null): bool
    {
        return $contentType === 'application/pdf';
    }

    protected function handlePdfMessage(string $whatsappUserId, string $from, string $body = null, string $mediaUrl = null): Response
    {
        try {
            // Store PDF info in session/cache for menu selection
            \Illuminate\Support\Facades\Cache::put("pdf_session_$whatsappUserId", [
                'media_url' => $mediaUrl,
                'timestamp' => now()
            ], 300); // 5 minutes cache

            // Send PDF menu
            $menuMessage = $this->getPdfMenu();
            $this->twilioService->sendText($from, $menuMessage);

            Log::info('PDF received - menu sent', [
                'whatsapp_user' => $whatsappUserId,
                'media_url' => $mediaUrl
            ]);

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Error handling PDF message', [
                'error' => $e->getMessage(),
                'whatsapp_user' => $whatsappUserId,
                'media_url' => $mediaUrl
            ]);

            $this->twilioService->sendText($from, "❌ Erreur lors du traitement. Veuillez réessayer.");
            return response('OK', 200);
        }
    }

    protected function handleTextCommand(string $whatsappUserId, string $from, string $body): Response
    {
        $body = trim(strtoupper($body));

        if ($body === 'HELP' || $body === 'AIDE') {
            $this->twilioService->sendText($from, $this->getHelpMessage());
            return response('OK', 200);
        }

        if ($body === 'STATUS' || $body === 'STATUT') {
            $this->twilioService->sendText($from, $this->getStatusMessage($whatsappUserId));
            return response('OK', 200);
        }

        // For other text commands, ask for PDF
        $this->twilioService->sendText($from, "📄 Veuillez envoyer un PDF avec votre commande.\n\nExemple: Envoyez un PDF avec le texte 'COMPRESS whatsapp'");
        return response('OK', 200);
    }

    protected function getHelpMessage(): string
    {
        return "🤖 *Bot PDF WhatsApp*\n\n" .
               "📄 *Envoyez un PDF avec une commande:*\n\n" .
               "🗜️ *COMPRESS [mode]*\n" .
               "   • whatsapp (défaut)\n" .
               "   • impression\n" .
               "   • équilibré\n\n" .
               "🔄 *CONVERT [format]*\n" .
               "   • docx\n" .
               "   • xlsx\n" .
               "   • img\n\n" .
               "👁️ *OCR* - Extrait le texte\n\n" .
               "📝 *SUMMARIZE [taille]*\n" .
               "   • short (défaut)\n" .
               "   • medium\n" .
               "   • long\n\n" .
               "🌍 *TRANSLATE [langue]*\n" .
               "   • fr, en, es, de...\n\n" .
               "🔒 *SECURE [option]*\n" .
               "   • password\n" .
               "   • watermark\n\n" .
               "ℹ️ Tapez STATUS pour voir vos tâches";
    }

    protected function getStatusMessage(string $whatsappUserId): string
    {
        $recentJobs = TaskJob::whereHas('document', function ($query) use ($whatsappUserId) {
            $query->where('whatsapp_user_id', $whatsappUserId);
        })
        ->with('document')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

        if ($recentJobs->isEmpty()) {
            return "📊 Aucune tâche récente trouvée.";
        }

        $message = "📊 *Vos dernières tâches:*\n\n";
        
        foreach ($recentJobs as $job) {
            $status = match($job->status) {
                'pending' => '⏳ En attente',
                'running' => '⚡ En cours',
                'completed' => '✅ Terminé',
                'failed' => '❌ Échoué',
                default => '❓ Inconnu'
            };

            $time = $job->created_at->diffForHumans();
            $message .= "• {$job->type} - {$status} ({$time})\n";
        }

        return $message;
    }

    /**
     * Check if this is the first interaction with a user
     */
    protected function isFirstInteraction(string $whatsappUserId): bool
    {
        // Check if user has any previous documents or jobs
        $hasInteracted = Document::where('whatsapp_user_id', $whatsappUserId)->exists();
        return !$hasInteracted;
    }

    /**
     * Send welcome message to new users
     */
    protected function sendWelcomeMessage(string $from): Response
    {
        $welcomeMessage = "🎉 *Bienvenue sur PDF Bot !*\n\n" .
                         "Je suis votre assistant intelligent pour traiter vos PDF.\n\n" .
                         "🔹 *Ce que je peux faire :*\n" .
                         "• Compresser vos PDF\n" .
                         "• Convertir en Word/Excel\n" .
                         "• Extraire le texte (OCR)\n" .
                         "• Faire des résumés automatiques\n" .
                         "• Traduire vos documents\n" .
                         "• Sécuriser avec mot de passe\n\n" .
                         "📎 *Pour commencer :*\n" .
                         "Envoyez-moi simplement un fichier PDF et je vous proposerai un menu d'options !\n\n" .
                         "💡 *Aide :* Tapez HELP à tout moment";

        $this->twilioService->sendText($from, $welcomeMessage);
        return response('OK', 200);
    }

    /**
     * Get PDF processing menu
     */
    protected function getPdfMenu(): string
    {
        return "📄 *PDF reçu !* Que voulez-vous faire ?\n\n" .
               "1️⃣ *Compresser* - Réduire la taille\n" .
               "2️⃣ *Convertir* - Vers Word/Excel/Image\n" .
               "3️⃣ *OCR* - Extraire le texte\n" .
               "4️⃣ *Résumer* - Résumé automatique\n" .
               "5️⃣ *Traduire* - Changer la langue\n" .
               "6️⃣ *Sécuriser* - Ajouter mot de passe\n\n" .
               "💬 *Répondez avec le numéro de votre choix* (1-6)";
    }

    /**
     * Handle menu selection (1-6)
     */
    protected function handleMenuSelection(string $whatsappUserId, string $from, string $selection): Response
    {
        // Get PDF info from cache
        $pdfSession = \Illuminate\Support\Facades\Cache::get("pdf_session_$whatsappUserId");
        
        if (!$pdfSession) {
            $this->twilioService->sendText($from, "❌ Session expirée. Veuillez renvoyer votre PDF.");
            return response('OK', 200);
        }

        // Map selections to commands
        $commands = [
            '1' => 'COMPRESS whatsapp',
            '2' => $this->getConvertSubmenu($from),
            '3' => 'OCR text',
            '4' => $this->getSummarizeSubmenu($from),
            '5' => $this->getTranslateSubmenu($from), 
            '6' => 'SECURE password',
            // Convert submenu
            '21' => 'CONVERT docx',
            '22' => 'CONVERT xlsx',
            '23' => 'CONVERT img',
            // Summarize submenu
            '41' => 'SUMMARIZE short',
            '42' => 'SUMMARIZE medium',
            '43' => 'SUMMARIZE long',
            // Translate submenu
            '51' => 'TRANSLATE fr',
            '52' => 'TRANSLATE en',
            '53' => 'TRANSLATE es',
            '54' => 'TRANSLATE de',
            '55' => 'TRANSLATE it'
        ];

        $command = $commands[$selection] ?? null;

        if (!$command) {
            $this->twilioService->sendText($from, "❌ Choix invalide. Choisissez entre 1 et 6.");
            return response('OK', 200);
        }

        // If command returns true, it means we sent a submenu
        if ($command === true) {
            return response('OK', 200);
        }

        // Process the command
        return $this->processPdfWithCommand($whatsappUserId, $from, $command, $pdfSession['media_url']);
    }

    /**
     * Show convert format submenu
     */
    protected function getConvertSubmenu(string $from): bool
    {
        $submenu = "📄 *Conversion* - Choisissez le format :\n\n" .
                   "2️⃣1️⃣ Word (.docx)\n" .
                   "2️⃣2️⃣ Excel (.xlsx)\n" .
                   "2️⃣3️⃣ Images (.jpg)\n\n" .
                   "💬 *Répondez avec le code* (21, 22 ou 23)";
        
        $this->twilioService->sendText($from, $submenu);
        return true;
    }

    /**
     * Show summarize submenu
     */
    protected function getSummarizeSubmenu(string $from): bool
    {
        $submenu = "📝 *Résumé* - Choisissez la taille :\n\n" .
                   "4️⃣1️⃣ Court (2-3 lignes)\n" .
                   "4️⃣2️⃣ Moyen (1 paragraphe)\n" .
                   "4️⃣3️⃣ Détaillé (plusieurs paragraphes)\n\n" .
                   "💬 *Répondez avec le code* (41, 42 ou 43)";
        
        $this->twilioService->sendText($from, $submenu);
        return true;
    }

    /**
     * Show translate submenu
     */
    protected function getTranslateSubmenu(string $from): bool
    {
        $submenu = "🌍 *Traduction* - Choisissez la langue :\n\n" .
                   "5️⃣1️⃣ Français\n" .
                   "5️⃣2️⃣ Anglais\n" .
                   "5️⃣3️⃣ Espagnol\n" .
                   "5️⃣4️⃣ Allemand\n" .
                   "5️⃣5️⃣ Italien\n\n" .
                   "💬 *Répondez avec le code* (51-55)";
        
        $this->twilioService->sendText($from, $submenu);
        return true;
    }

    /**
     * Process PDF with selected command
     */
    protected function processPdfWithCommand(string $whatsappUserId, string $from, string $command, string $mediaUrl): Response
    {
        try {
            $parsedCommand = $this->commandParser->parse($command);

            if (!$parsedCommand) {
                $this->twilioService->sendText($from, "❌ Erreur de traitement. Veuillez réessayer.");
                return response('OK', 200);
            }

            // Create Document record
            $document = Document::create([
                'original_name' => 'whatsapp_pdf_' . now()->format('Y-m-d_H-i-s') . '.pdf',
                'whatsapp_user_id' => $whatsappUserId,
                'status' => 'pending',
                'metadata' => [
                    'media_url' => $mediaUrl,
                    'command' => $command,
                    'from' => $from
                ],
                'expires_at' => now()->addHours(24)
            ]);

            // Create TaskJob record
            $taskJob = TaskJob::create([
                'document_id' => $document->id,
                'type' => $parsedCommand['type'],
                'status' => 'pending',
                'parameters' => $parsedCommand['parameters']
            ]);

            // Dispatch appropriate job
            $jobClass = $parsedCommand['job_class'];
            $jobClass::dispatch($document, $taskJob, $from);

            // Send confirmation message with operation details
            $operationName = $this->getOperationName($parsedCommand['type']);
            $this->twilioService->sendText($from, "⚡ *$operationName en cours...*\n\nVous recevrez le résultat dans quelques instants !");

            // Clear PDF session
            \Illuminate\Support\Facades\Cache::forget("pdf_session_$whatsappUserId");

            Log::info('PDF processing job dispatched via menu', [
                'document_id' => $document->id,
                'task_job_id' => $taskJob->id,
                'type' => $parsedCommand['type'],
                'whatsapp_user' => $whatsappUserId,
                'selection_method' => 'menu'
            ]);

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Error processing PDF with command', [
                'error' => $e->getMessage(),
                'whatsapp_user' => $whatsappUserId,
                'command' => $command,
                'media_url' => $mediaUrl
            ]);

            $this->twilioService->sendText($from, "❌ Erreur lors du traitement. Veuillez réessayer.");
            return response('OK', 200);
        }
    }

    /**
     * Get friendly operation name
     */
    protected function getOperationName(string $type): string
    {
        return match($type) {
            'compress' => 'Compression PDF',
            'convert' => 'Conversion PDF',
            'ocr' => 'Extraction de texte',
            'summarize' => 'Résumé automatique',
            'translate' => 'Traduction',
            'secure' => 'Sécurisation PDF',
            default => 'Traitement PDF'
        };
    }
}
