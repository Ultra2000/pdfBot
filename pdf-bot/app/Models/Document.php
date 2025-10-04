<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\StorageService;
use Carbon\Carbon;

class Document extends Model
{
    protected $fillable = [
        'original_name',
        's3_path',
        's3_output_path',
        'file_size',
        'output_file_size',
        'mime_type',
        'whatsapp_user_id',
        'status',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'file_size' => 'integer',
        'output_file_size' => 'integer',
    ];

    public function taskJobs(): HasMany
    {
        return $this->hasMany(TaskJob::class);
    }

    public function latestTaskJob(): HasMany
    {
        return $this->hasMany(TaskJob::class)->latest();
    }

    /**
     * Get a temporary download URL for the document
     */
    public function getTemporaryUrl(int $minutes = 60): ?string
    {
        if (!$this->s3_path) {
            return null;
        }

        $storageService = app(StorageService::class);
        return $storageService->getTemporaryUrl($this->s3_path, $minutes);
    }

    /**
     * Get a temporary download URL for the processed output
     */
    public function getOutputTemporaryUrl(int $minutes = 60): ?string
    {
        if (!$this->s3_output_path) {
            return null;
        }

        $storageService = app(StorageService::class);
        return $storageService->getTemporaryUrl($this->s3_output_path, $minutes);
    }

    /**
     * Check if document is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Set expiration time (default: 24 hours from now)
     */
    public function setExpiration(int $hours = 24): void
    {
        $this->expires_at = Carbon::now()->addHours($hours);
        $this->save();
    }

    /**
     * Scope for expired documents
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', Carbon::now());
    }

    /**
     * Scope for active (non-expired) documents
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', Carbon::now());
        });
    }
}
