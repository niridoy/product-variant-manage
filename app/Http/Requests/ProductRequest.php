<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "title"         => "required",
            "sku"           => "required|unique:products,sku,id,".$this->id,
            "description"   => "required",
            "product_image"    => "required|array",
            "product_variant"    => "required|array",
            "product_variant_prices"    => "required|array"
        ];
    }
}
