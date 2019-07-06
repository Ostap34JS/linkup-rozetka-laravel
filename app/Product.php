<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title',
        'description',
        'price',
        'original_id'
    ];

    public function categories()
    {
        return $this->belongsToMany('App\Category', 'product_category');
    }

    public function images()
    {
        return $this->hasMany('App\Image');
    }
}
