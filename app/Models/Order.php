<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'discount',
        'total',
        'status',
        'delivery_status',
        'payment_status',
        'user_id',
        'reference',
        'promo_code_id',
        'address',
        'city',
        'payment_method',
    ];

    protected $searchableFields = ['*'];

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
