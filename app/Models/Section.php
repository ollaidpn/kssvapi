<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Section extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'type',
        'title',
        'subtitle',
        'description',
        'image1',
        'image2',
        'btn',
        'link',
    ];

    protected $searchableFields = ['*'];
}
