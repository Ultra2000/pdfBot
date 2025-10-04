<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskJob extends Model
{
    protected $fillable = [
        'document_id',
        'type',
        'status',
        'parameters',
        'error_message',
        'result_metadata',
        'started_at',
        'completed_at',
        'processing_time_seconds',
    ];

    protected $casts = [
        'parameters' => 'array',
        'result_metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'processing_time_seconds' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
