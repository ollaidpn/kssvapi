<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'user_id',
        'type',
        'item_id',
        'item_infos',
        'price',
        'qty',
        'total',
        'status',
        'order_id',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'item_infos' => 'array',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
