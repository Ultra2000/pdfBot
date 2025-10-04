<?php

namespace App\Services\Messaging;

interface MessagingProvider
{
    /**
     * Send a text message
     */
    public function sendText(string $to, string $message): bool;

    /**
     * Send a media file with optional caption
     */
    public function sendMedia(string $to, string $mediaUrl, string $caption = null): bool;

    /**
     * Get provider name
     */
    public function getProviderName(): string;
}
