<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'client_id', 'title', 'description', 'tracking_code',
        'status', 'type', 'progress', 'started_at', 'estimated_end_at',
        'completed_at', 'tech_stack', 'internal_notes', 'is_portfolio', 'portfolio_data',
    ];

    protected function casts(): array
    {
        return [
            'tech_stack'       => 'array',
            'portfolio_data'   => 'array',
            'is_portfolio'     => 'boolean',
            'started_at'       => 'datetime',
            'estimated_end_at' => 'datetime',
            'completed_at'     => 'datetime',
        ];
    }

    // ── Relations ─────────────────────────────────────────────

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProjectStep::class)->orderBy('order');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopePublicPortfolio($query)
    {
        return $query->where('is_portfolio', true)->where('status', 'completed');
    }

    public function scopeByTrackingCode($query, string $code)
    {
        return $query->where('tracking_code', strtoupper($code));
    }

    // ── Génération du code de suivi ───────────────────────────

    public static function generateTrackingCode(): string
    {
        $year   = date('Y');
        $prefix = "GT-{$year}-";
        $last   = static::where('tracking_code', 'like', $prefix . '%')
            ->orderByDesc('tracking_code')
            ->value('tracking_code');
        $next = $last ? (intval(substr($last, -3)) + 1) : 1;

        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    // ── Données publiques (sans infos sensibles) ──────────────

    public function toPublicTracking(): array
    {
        return [
            'tracking_code'    => $this->tracking_code,
            'title'            => $this->title,
            'status'           => $this->status,
            'type'             => $this->type,
            'progress'         => $this->progress,
            'started_at'       => $this->started_at?->toDateString(),
            'estimated_end_at' => $this->estimated_end_at?->toDateString(),
            'steps'            => $this->steps->map->toPublicStep()->values(),
        ];
    }
}