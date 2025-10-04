<?php

namespace App\Services\Messaging;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService implements MessagingProvider
{
    protected Client $client;
    protected string $fromNumber;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );
        
        $this->fromNumber = config('services.twilio.whatsapp_number');
    }

    public function sendText(string $to, string $message): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => $message
            ]);

            Log::info('WhatsApp text message sent', [
                'to' => $to,
                'provider' => 'twilio',
                'message_length' => strlen($message)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp text message', [
                'to' => $to,
                'error' => $e->getMessage(),
                'provider' => 'twilio'
            ]);

            return false;
        }
    }

    public function sendMedia(string $to, string $mediaUrl, string $caption = null): bool
    {
        try {
            $messageData = [
                'from' => $this->fromNumber,
                'mediaUrl' => [$mediaUrl]
            ];

            if ($caption) {
                $messageData['body'] = $caption;
            }

            $this->client->messages->create($to, $messageData);

            Log::info('WhatsApp media message sent', [
                'to' => $to,
                'media_url' => $mediaUrl,
                'caption_length' => $caption ? strlen($caption) : 0,
                'provider' => 'twilio'
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp media message', [
                'to' => $to,
                'media_url' => $mediaUrl,
                'error' => $e->getMessage(),
                'provider' => 'twilio'
            ]);

            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'twilio';
    }

    /**
     * Validate incoming webhook signature
     */
    public function validateSignature(string $url, array $postData, string $signature): bool
    {
        $authToken = config('services.twilio.auth_token');
        
        return \Twilio\Security\RequestValidator::validate(
            $authToken,
            $signature,
            $url,
            $postData
        );
    }
}
