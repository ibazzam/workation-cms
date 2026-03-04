<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportProviderJob extends Model
{
    use HasFactory;

    protected $table = 'transport_provider_jobs';

    protected $fillable = [
        'provider', 'action', 'payload', 'status', 'attempts', 'next_attempt_at', 'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'next_attempt_at' => 'datetime',
    ];

    public function markProcessing()
    {
        $this->status = 'processing';
        $this->save();
    }

    public function markCompleted()
    {
        $this->status = 'completed';
        $this->save();
    }

    public function markFailed(string $error, int $backoffSeconds = 60)
    {
        $this->attempts += 1;
        $this->last_error = $error;
        $this->next_attempt_at = now()->addSeconds($backoffSeconds * $this->attempts);
        $this->status = 'failed';
        $this->save();
    }
}
