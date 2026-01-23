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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
