<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'order_id',
        'amount',
        'paid_by',
        'date',
        'reference',
        'transaction_id',
        'status',
        'payment_link',
        'expires_at',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'date' => 'date',
        'expires_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
