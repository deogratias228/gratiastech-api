<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStep extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_id', 'title', 'description',
        'order', 'status', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relations ─────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // ── Données publiques ─────────────────────────────────────

    public function toPublicStep(): array
    {
        return [
            'title'        => $this->title,
            'description'  => $this->description,
            'status'       => $this->status,
            'order'        => $this->order,
            'started_at'   => $this->started_at?->toDateString(),
            'completed_at' => $this->completed_at?->toDateString(),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCurrent(): bool
    {
        return $this->status === 'in_progress';
    }
}