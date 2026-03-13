<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'author_id', 'title', 'excerpt', 'content',
        'slug', 'cover_image', 'tags', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'title'        => 'array',
            'excerpt'      => 'array',
            'content'      => 'array',
            'tags'         => 'array',
            'published_at' => 'datetime',
        ];
    }

    // ── Relations ─────────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    // ── Helpers multilingues ──────────────────────────────────

    public function getTitle(string $locale = 'fr'): string
    {
        return $this->title[$locale] ?? $this->title['fr'] ?? '';
    }

    public function getExcerpt(string $locale = 'fr'): string
    {
        return $this->excerpt[$locale] ?? $this->excerpt['fr'] ?? '';
    }

    public function getContent(string $locale = 'fr'): string
    {
        return $this->content[$locale] ?? $this->content['fr'] ?? '';
    }
}
