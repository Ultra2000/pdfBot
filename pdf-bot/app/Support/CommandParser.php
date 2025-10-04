<?php

namespace App\Support;

use App\Jobs\CompressPdfJob;
use App\Jobs\ConvertPdfJob;
use App\Jobs\OcrPdfJob;
use App\Jobs\SummarizePdfJob;
use App\Jobs\TranslatePdfJob;
use App\Jobs\SecurePdfJob;

class CommandParser
{
    protected array $supportedCommands = [
        'COMPRESS' => [
            'job_class' => CompressPdfJob::class,
            'type' => 'compress',
            'modes' => ['whatsapp', 'impression', 'équilibré', 'balanced']
        ],
        'CONVERT' => [
            'job_class' => ConvertPdfJob::class,
            'type' => 'convert',
            'formats' => ['docx', 'xlsx', 'img', 'image']
        ],
        'OCR' => [
            'job_class' => OcrPdfJob::class,
            'type' => 'ocr',
            'output_formats' => ['text', 'docx']
        ],
        'SUMMARIZE' => [
            'job_class' => SummarizePdfJob::class,
            'type' => 'summarize',
            'sizes' => ['short', 'medium', 'long', 'court', 'moyen', 'détaillé']
        ],
        'TRANSLATE' => [
            'job_class' => TranslatePdfJob::class,
            'type' => 'translate',
            'languages' => ['fr', 'en', 'es', 'de', 'it', 'pt', 'ru', 'ar', 'zh']
        ],
        'SECURE' => [
            'job_class' => SecurePdfJob::class,
            'type' => 'secure',
            'options' => ['password', 'watermark', 'both']
        ]
    ];

    /**
     * Parse a command string into job parameters
     */
    public function parse(string $command): ?array
    {
        $command = trim($command);
        if (empty($command)) {
            return null;
        }

        // Split command into parts
        $parts = preg_split('/\s+/', strtoupper($command));
        $action = $parts[0] ?? '';
        $parameter = $parts[1] ?? '';

        if (!isset($this->supportedCommands[$action])) {
            return null;
        }

        $commandConfig = $this->supportedCommands[$action];

        return [
            'type' => $commandConfig['type'],
            'job_class' => $commandConfig['job_class'],
            'parameters' => $this->parseParameters($action, $parameter, $commandConfig)
        ];
    }

    /**
     * Parse parameters based on command type
     */
    protected function parseParameters(string $action, string $parameter, array $config): array
    {
        $parameters = ['original_command' => $action . ' ' . $parameter];

        switch ($action) {
            case 'COMPRESS':
                $mode = $this->validateParameter($parameter, $config['modes']) ?: 'whatsapp';
                $parameters['mode'] = $mode;
                break;

            case 'CONVERT':
                $format = $this->validateParameter($parameter, $config['formats']) ?: 'docx';
                // Normalize format
                if ($format === 'image') $format = 'img';
                $parameters['format'] = $format;
                break;

            case 'OCR':
                $outputFormat = $this->validateParameter($parameter, $config['output_formats']) ?: 'text';
                $parameters['output_format'] = $outputFormat;
                break;

            case 'SUMMARIZE':
                $size = $this->validateParameter($parameter, $config['sizes']) ?: 'short';
                // Normalize French to English
                $sizeMap = [
                    'court' => 'short',
                    'moyen' => 'medium',
                    'détaillé' => 'long'
                ];
                $parameters['size'] = $sizeMap[$size] ?? $size;
                break;

            case 'TRANSLATE':
                $language = $this->validateParameter($parameter, $config['languages']) ?: 'en';
                $parameters['target_language'] = $language;
                break;

            case 'SECURE':
                $option = $this->validateParameter($parameter, $config['options']) ?: 'password';
                $parameters['security_type'] = $option;
                
                // Generate a random password if needed
                if (in_array($option, ['password', 'both'])) {
                    $parameters['password'] = $this->generatePassword();
                }
                break;
        }

        return $parameters;
    }

    /**
     * Validate parameter against allowed values
     */
    protected function validateParameter(string $parameter, array $allowedValues): ?string
    {
        $parameter = strtolower($parameter);
        return in_array($parameter, $allowedValues) ? $parameter : null;
    }

    /**
     * Generate a secure random password
     */
    protected function generatePassword(int $length = 8): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    }

    /**
     * Get list of supported commands for help
     */
    public function getSupportedCommands(): array
    {
        return array_keys($this->supportedCommands);
    }

    /**
     * Check if a command is supported
     */
    public function isSupported(string $command): bool
    {
        $action = strtoupper(explode(' ', trim($command))[0]);
        return isset($this->supportedCommands[$action]);
    }
}
