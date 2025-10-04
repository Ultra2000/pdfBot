<?php

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\MessagingProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaProvider implements MessagingProvider
{
    private string $accessToken;
    private string $phoneNumberId;
    private string $apiVersion;
    private PendingRequest $client;

    public function __construct()
    {
        $this->accessToken = config('messaging.meta.access_token');
        $this->phoneNumberId = config('messaging.meta.phone_number_id');
        $this->apiVersion = config('messaging.meta.api_version', 'v18.0');
        
        $this->client = Http::withToken($this->accessToken)
            ->baseUrl("https://graph.facebook.com/{$this->apiVersion}")
            ->timeout(30)
            ->retry(3, 1000);
    }

    public function sendText(string $to, string $message): bool
    {
        try {
            $response = $this->client->post("{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ]);

            if ($response->successful()) {
                Log::info('Meta WhatsApp text message sent', [
                    'to' => $to,
                    'message_id' => $response->json('messages.0.id'),
                    'message_length' => strlen($message)
                ]);
                return true;
            }

            $this->logError('send_text', $response, $to);
            return false;

        } catch (\Exception $e) {
            Log::error('Meta WhatsApp send text exception', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function sendDocument(string $to, string $documentUrl, string $caption = '', string $filename = ''): bool
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'document',
                'document' => [
                    'link' => $documentUrl
                ]
            ];

            if (!empty($caption)) {
                $payload['document']['caption'] = $caption;
            }

            if (!empty($filename)) {
                $payload['document']['filename'] = $filename;
            }

            $response = $this->client->post("{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                Log::info('Meta WhatsApp document sent', [
                    'to' => $to,
                    'document_url' => $documentUrl,
                    'message_id' => $response->json('messages.0.id'),
                    'filename' => $filename
                ]);
                return true;
            }

            $this->logError('send_document', $response, $to);
            return false;

        } catch (\Exception $e) {
            Log::error('Meta WhatsApp send document exception', [
                'to' => $to,
                'document_url' => $documentUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function sendImage(string $to, string $imageUrl, string $caption = ''): bool
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'image',
                'image' => [
                    'link' => $imageUrl
                ]
            ];

            if (!empty($caption)) {
                $payload['image']['caption'] = $caption;
            }

            $response = $this->client->post("{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                Log::info('Meta WhatsApp image sent', [
                    'to' => $to,
                    'image_url' => $imageUrl,
                    'message_id' => $response->json('messages.0.id')
                ]);
                return true;
            }

            $this->logError('send_image', $response, $to);
            return false;

        } catch (\Exception $e) {
            Log::error('Meta WhatsApp send image exception', [
                'to' => $to,
                'image_url' => $imageUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'meta';
    }

    /**
     * Format phone number for Meta API (remove whatsapp: prefix if present)
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        return str_replace('whatsapp:', '', $phoneNumber);
    }

    /**
     * Log API errors with detailed information
     */
    private function logError(string $operation, $response, string $to): void
    {
        $errorData = $response->json();
        
        Log::error("Meta WhatsApp {$operation} failed", [
            'to' => $to,
            'status_code' => $response->status(),
            'error_code' => $errorData['error']['code'] ?? 'unknown',
            'error_message' => $errorData['error']['message'] ?? 'Unknown error',
            'error_subcode' => $errorData['error']['error_subcode'] ?? null,
            'full_response' => $errorData
        ]);
    }

    /**
     * Get media URL from Meta API
     */
    public function getMediaUrl(string $mediaId): ?string
    {
        try {
            $response = $this->client->get($mediaId);
            
            if ($response->successful()) {
                return $response->json('url');
            }
            
            Log::error('Failed to get media URL from Meta API', [
                'media_id' => $mediaId,
                'response' => $response->json()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting media URL from Meta API', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
