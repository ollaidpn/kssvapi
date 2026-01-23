<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppInfo extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'ccphone1',
        'phone1',
        'ccphone2',
        'phone2',
        'email1',
        'email2',
        'latitude',
        'longitude',
        'logo_color',
        'logo_white',
        'address',
        'town',
        'country',
        'maintenance',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'app_infos';

    protected $casts = [
        'maintenance' => 'boolean',
    ];
}
