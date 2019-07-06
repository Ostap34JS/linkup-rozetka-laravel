<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'original',
        'big',
        'preview',
        'large',
        'product_id',
    ];
}
