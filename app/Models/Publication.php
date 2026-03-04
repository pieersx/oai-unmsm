<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    protected $table = 'publications';

    protected $fillable = [
        'title',
        'author',
        'description',
        'date',
        'identifier',
        'subject',
        'type',
        'language',
        'publisher',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];
}
