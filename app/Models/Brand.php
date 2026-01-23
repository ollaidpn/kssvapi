<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
    ];

    /**
     * Articles de cette marque
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
