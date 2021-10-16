<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{

    public function one()
    {
        return $this->belongsTo(ProductVariant::class,'product_variant_one','id')->withDefault();
    }

    public function two()
    {
        return $this->belongsTo(ProductVariant::class,'product_variant_two','id')->withDefault();
    }

    public function three()
    {
        return $this->belongsTo(ProductVariant::class,'product_variant_three','id')->withDefault();
    }
}
