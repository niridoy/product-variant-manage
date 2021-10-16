<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];


    public function images()
    {
        return $this->hasMany(ProductImage::class,'product_id','id');
    }

    // public function variants()
    // {
    //     return $this->hasMany(ProductVariant::class,'product_id','id');
    // }

    public function variants()
    {
        return $this->hasMany(ProductVariantPrice::class,'product_id','id');
    }

    public function getFormatedCreatedDateAttribute()
    {
        Carbon::parse($this->created_at)->format('d-m-Y');
    }

    // public function getShowVariantsAttribute()
    // {   $variants = '';
    //     foreach($this->variants as $variant) {
    //         $variants .= $variant->variant;
    //     }
    //     // $this->variants->map(function($variant) use ($variants){
    //     //     $variants .= $variant->variant;
    //     // });

    //     return $variants;
    // }

}
