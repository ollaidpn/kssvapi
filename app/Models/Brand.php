<?php

namespace App\Models;

use App\Helpers\Shortcut;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_id',
        'og_id',
        'name',
        'logo',
        'original_logo',
    ];

    /**
     * Relation avec la table synchronizations
     */
    public function synchronization(): BelongsTo
    {
        return $this->belongsTo(Synchronization::class, 'sync_id');
    }

    /**
     * Articles de cette marque
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Retourne l'URL complète du logo
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }
        return Shortcut::fileExistsOnServer($this->logo);
    }
}
