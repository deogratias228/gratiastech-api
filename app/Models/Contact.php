<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name', 'email', 'phone', 'company',
        'subject', 'message', 'service_interest',
        'status', 'replied_at', 'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    // ── Helpers ───────────────────────────────────────────────

    public function markAsRead(): void
    {
        $this->update(['status' => 'read']);
    }

    public function markAsReplied(): void
    {
        $this->update(['status' => 'replied', 'replied_at' => now()]);
    }
}
