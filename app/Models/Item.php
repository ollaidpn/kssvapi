<?php

namespace App\Models;

use App\Helpers\Shortcut;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'sale_price',
        'category_id',
        'brand_id',
        'image',
        'images',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'images' => 'array',
    ];

    /**
     * Catégorie de l'article
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(LocalCategory::class, 'category_id');
    }

    /**
     * Marque de l'article
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Retourne l'URL complète de l'image principale
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        return Shortcut::fileExistsOnServer($this->image);
    }

    /**
     * Retourne les URLs complètes de la galerie d'images
     */
    public function getImagesUrlsAttribute(): array
    {
        if (!$this->images || !is_array($this->images)) {
            return [];
        }
        return array_map(function ($img) {
            return Shortcut::fileExistsOnServer($img);
        }, $this->images);
    }
}
