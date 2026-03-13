<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['title', 'description', 'icon', 'order', 'is_active'];

    protected function casts(): array
    {
        return [
            'title'       => 'array',
            'description' => 'array',
            'is_active'   => 'boolean',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }

    // ── Helpers multilingues ──────────────────────────────────

    public function getTitle(string $locale = 'fr'): string
    {
        return $this->title[$locale] ?? $this->title['fr'] ?? '';
    }

    public function getDescription(string $locale = 'fr'): string
    {
        return $this->description[$locale] ?? $this->description['fr'] ?? '';
    }
}