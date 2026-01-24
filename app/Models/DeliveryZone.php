<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'integer',
    ];

    /**
     * Relation avec la zone parente
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'parent_id');
    }

    /**
     * Relation avec les sous-zones
     */
    public function children(): HasMany
    {
        return $this->hasMany(DeliveryZone::class, 'parent_id')->orderBy('name');
    }

    /**
     * Scope pour récupérer uniquement les zones parentes
     */
    public function scopeParentZones($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope pour récupérer uniquement les zones actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Vérifie si c'est une zone parente
     */
    public function isParent(): bool
    {
        return $this->parent_id === null;
    }
}
