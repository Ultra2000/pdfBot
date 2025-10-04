<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    private WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Handle webhook verification (GET)
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('messaging.meta.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Meta webhook verification successful');
            return response($challenge, 200);
        }

        Log::warning('Meta webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
            'expected_token' => $verifyToken
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming messages (POST)
     */
    public function handle(Request $request): Response
    {
        try {
            // Verify webhook signature
            if (!$this->verifySignature($request)) {
                Log::warning('Meta webhook signature verification failed');
                return response('Unauthorized', 401);
            }

            $payload = $request->all();
            Log::info('Meta webhook received', ['payload' => $payload]);

            // Process webhook data
            if (isset($payload['entry'])) {
                foreach ($payload['entry'] as $entry) {
                    if (isset($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            if ($change['field'] === 'messages') {
                                $this->processMessage($change['value']);
                            }
                        }
                    }
                }
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Meta webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Process incoming message
     */
    private function processMessage(array $messageData): void
    {
        if (!isset($messageData['messages'])) {
            return;
        }

        foreach ($messageData['messages'] as $message) {
            $from = $message['from'] ?? null;
            $messageId = $message['id'] ?? null;
            $timestamp = $message['timestamp'] ?? null;

            if (!$from || !$messageId) {
                Log::warning('Invalid message data', ['message' => $message]);
                continue;
            }

            // Format phone number for consistency with Twilio format
            $formattedFrom = 'whatsapp:+' . $from;

            // Handle different message types
            if (isset($message['text'])) {
                $this->handleTextMessage($formattedFrom, $message['text']['body'], $messageId);
            } elseif (isset($message['document'])) {
                $this->handleDocumentMessage($formattedFrom, $message['document'], $messageId);
            } elseif (isset($message['image'])) {
                $this->handleImageMessage($formattedFrom, $message['image'], $messageId);
            }
        }
    }

    /**
     * Handle text message
     */
    private function handleTextMessage(string $from, string $text, string $messageId): void
    {
        Log::info('Meta text message received', [
            'from' => $from,
            'text' => $text,
            'message_id' => $messageId
        ]);

        $this->whatsAppService->handleTextMessage($from, $text);
    }

    /**
     * Handle document message
     */
    private function handleDocumentMessage(string $from, array $document, string $messageId): void
    {
        $mediaId = $document['id'] ?? null;
        $filename = $document['filename'] ?? 'document';
        $mimeType = $document['mime_type'] ?? '';

        if (!$mediaId) {
            Log::warning('Document message without media ID', ['document' => $document]);
            return;
        }

        Log::info('Meta document message received', [
            'from' => $from,
            'media_id' => $mediaId,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'message_id' => $messageId
        ]);

        // Get media URL from Meta API
        $mediaUrl = app(\App\Services\WhatsApp\Providers\MetaProvider::class)->getMediaUrl($mediaId);
        
        if ($mediaUrl) {
            $this->whatsAppService->handleMediaMessage($from, $mediaUrl, $mimeType, $filename);
        } else {
            Log::error('Failed to get media URL for document', ['media_id' => $mediaId]);
        }
    }

    /**
     * Handle image message
     */
    private function handleImageMessage(string $from, array $image, string $messageId): void
    {
        $mediaId = $image['id'] ?? null;
        $mimeType = $image['mime_type'] ?? '';

        if (!$mediaId) {
            Log::warning('Image message without media ID', ['image' => $image]);
            return;
        }

        Log::info('Meta image message received', [
            'from' => $from,
            'media_id' => $mediaId,
            'mime_type' => $mimeType,
            'message_id' => $messageId
        ]);

        // Get media URL from Meta API
        $mediaUrl = app(\App\Services\WhatsApp\Providers\MetaProvider::class)->getMediaUrl($mediaId);
        
        if ($mediaUrl) {
            $this->whatsAppService->handleMediaMessage($from, $mediaUrl, $mimeType, 'image');
        } else {
            Log::error('Failed to get media URL for image', ['media_id' => $mediaId]);
        }
    }

    /**
     * Verify webhook signature
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }

        $appSecret = config('messaging.meta.app_secret');
        $payload = $request->getContent();
        
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
        
        return hash_equals($expectedSignature, $signature);
    }
}
