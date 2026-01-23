<?php

namespace App\Models;

use App\Helpers\Shortcut;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalCategory extends Model
{
    use HasFactory;

    protected $table = 'local_categories';

    protected $fillable = [
        'sync_id',
        'name',
        'logo',
        'original_logo',
        'parent_id',
    ];

    /**
     * Relation avec la table synchronizations
     */
    public function synchronization(): BelongsTo
    {
        return $this->belongsTo(Synchronization::class, 'sync_id');
    }

    /**
     * Catégorie parente
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(LocalCategory::class, 'parent_id');
    }

    /**
     * Sous-catégories
     */
    public function children(): HasMany
    {
        return $this->hasMany(LocalCategory::class, 'parent_id');
    }

    /**
     * Articles dans cette catégorie
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
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
