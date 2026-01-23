<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoCode extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'message',
        'code',
        'discount_by',
        'discount_value',
        'filter_by',
        'limite',
        'expiration',
        'status',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'promo_codes';

    protected $casts = [
        'expiration' => 'date',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
