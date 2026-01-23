<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Synchronization extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'type_id',
        'data',
        'status',
        'last_sync_at',
        'last_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'last_sync_at' => 'datetime',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_UNSYNC = 'unsync';
    const STATUS_SYNCED = 'synced';
    const STATUS_CHANGED = 'changed';
    const STATUS_ERROR = 'error';

    /**
     * Type constants
     */
    const TYPE_ITEM = 'item';
    const TYPE_CATEGORY = 'category';
    const TYPE_BRAND = 'brand';

    /**
     * Get the name from data
     */
    public function getNameAttribute(): string
    {
        if ($this->type === self::TYPE_ITEM) {
            return $this->data['nom'] ?? $this->data['libelle'] ?? 'Sans nom';
        }
        if ($this->type === self::TYPE_CATEGORY) {
            return $this->data['nom'] ?? 'Sans nom';
        }
        return $this->data['name'] ?? $this->data['nom'] ?? 'Sans nom';
    }

    /**
     * Scope for filtering by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
