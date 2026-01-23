<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentLog extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'reference',
        'user_id',
        'user_info',
        'items',
        'promo_code_id',
        'discount',
        'total',
        'payment_method',
        'transaction_id',
        'payment_link',
        'payment_qrcode',
        'expires_at',
        'status',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'user_info' => 'array',
        'items' => 'array',
        'expires_at' => 'datetime',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the user that owns the payment log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the promo code used for this payment.
     */
    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
