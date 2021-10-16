<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Validator;
class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $ProductVariantNames = ProductVariant::distinct('variant')
            ->select('variant')
            ->orderBy('variant','ASC')
            ->get();

        if ($request->title != null
            || $request->variant != null
            ||  $request->price_from != null
            ||  $request->price_to != null
            || $request->date != null ) {

            $Products =  $this->productFilter($request);

        } else {
            $Products = Product::with('variants')
                ->paginate(2);
        }
        return view('products.index',compact('Products','ProductVariantNames'));
    }

    public function productFilter($request) {
        if ($request->variant != null) {
            $ProductVariantIds = ProductVariant::distinct('variant')
            ->where('variant','like', $request->variant)
            ->pluck('id');
        } else {
            $ProductVariantIds  = [];
        }

        return Product::with(['variants' => function($query) use ($ProductVariantIds,$request) {
            $query->when(count($ProductVariantIds) > 0,function($query) use ($ProductVariantIds) {
                $query->whereIn('id',ProductVariantPrice::select('id')
                    ->whereIn('product_variant_one',$ProductVariantIds)
                    ->orWhereIn('product_variant_two',$ProductVariantIds)
                    ->orWhereIn('product_variant_three',$ProductVariantIds)
                    ->pluck('id')
                );
            })
            ->when($request->price_from && $request->price_to,function($query) use ($request) {
                $query->wherebetween('price',[(int)$request->price_from,(int)$request->price_to]);
            })
            ->when($request->price_from || $request->price_to,function($query) use ($request) {
                if ($request->price_to) {
                    $query->where('price','<=',$request->price_to);
                } else {
                    $query->where('price','>=',$request->price_from);
                }
            })
            ->when($request->date,function($query) use ($request) {
                $query->whereDate('created_at',Carbon::parse($request->date)->format('Y-m-d'));
            });
        }])
        ->when($request->title,function($query) use($request) {
            $query->where('title','like', '%' . $request->title . '%');
        })
        ->when(isset($ProductVariantIds) || $request->has('price_from') || $request->has('price_to') ,function($query) use($ProductVariantIds,$request) {
            $query->whereHas('variants',function($query) use ($ProductVariantIds,$request) {
                $query->when(count($ProductVariantIds) > 0 ,function($query) use ($ProductVariantIds) {
                    $query->whereIn('id',ProductVariantPrice::select('id')
                        ->whereIn('product_variant_one',$ProductVariantIds)
                        ->orWhereIn('product_variant_two',$ProductVariantIds)
                        ->orWhereIn('product_variant_three',$ProductVariantIds)
                        ->pluck('id')
                    );
                })
                ->when($request->price_from && $request->price_to,function($query) use ($request) {
                    $query->wherebetween('price',[(int)$request->price_from,(int)$request->price_to]);
                })
                ->when($request->price_from || $request->price_to,function($query) use ($request) {
                    if ($request->price_to) {
                        $query->where('price','<=',$request->price_to);
                    } else {
                        $query->where('price','>=',$request->price_from);
                    }
                })
                ->when($request->date,function($query) use ($request) {
                    $query->whereDate('created_at',Carbon::parse($request->date)->format('Y-m-d'));
                });
            });
        })
        ->paginate(2);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $rules = array(
            "title"         => "required",
            "sku"           => "required|unique:products,sku",
            "description"   => "required",
            "product_image"    => "nullable|array",
            "product_variant"    => "required|array",
            "product_variant_prices"    => "required|array"
        );

        $validator = Validator::make( $request->all(), $rules);

        if ( $validator->fails() ){
            return [
                'success' => false,
                'message' => 'Validation failed !',
                'errors' =>  $validator->errors()
            ];
        }

        try {
            DB::beginTransaction();
                $Product = Product::create([
                    'title' => $request->title,
                    'sku' => $request->sku,
                    'description' => $request->description,
                ]);
                $ProductVariant = Collect($request->product_variant);

                for ($i=0; $i < count($request->product_variant_prices); $i++) {
                    $ProductVariantDetails = $request->product_variant_prices[$i];
                    $Variants = explode('/',$ProductVariantDetails['title']);
                    $ProductVariantPrice = new ProductVariantPrice;
                    foreach($Variants as $key => $VariantValue) {
                        foreach($ProductVariant as $VariantOption) {
                            if(in_array($VariantValue,$VariantOption['tags'])){
                                $ProductVainetValue = ProductVariant::create([
                                    'variant' => $VariantValue,
                                    'variant_id' => $VariantOption['option'],
                                    'product_id' => $Product->id
                                ]);

                                if($key == 0) {
                                    $ProductVariantPrice->product_variant_one = $ProductVainetValue->id;
                                } else if ( $key == 1 ) {
                                    $ProductVariantPrice->product_variant_two = $ProductVainetValue->id;
                                } else if ( $key == 2 ) {
                                    $ProductVariantPrice->product_variant_three = $ProductVainetValue->id;
                                }

                            }
                        }
                    }
                    $ProductVariantPrice->product_id = $Product->id;
                    $ProductVariantPrice->price = $ProductVariantDetails['price'];
                    $ProductVariantPrice->stock = $ProductVariantDetails['stock'];
                    $Product->images()->save($ProductVariantPrice);
                }

                for ($i=0; $i < count($request->product_image); $i++) {
                    $Product->images()->create([
                        'file_path' => $request->product_image[$i],
                        'thumbnail' => ($i== 0) ? 1 : 0
                    ]);
                }
            DB::commit();
            return [
                'success' => true,
                'message' => 'Product has been created successfully !'
            ];
        } catch (\Throwable $th) { dd($th);
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Somethings wrong !'
            ];
        }
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $product->load('images','variants','variants.one','variants.two','variants.three');
        $variants = Variant::all();
        return view('products.edit', compact('variants','product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $Product)
    {
        $rules = array(
            "title"         => "required",
            "sku"           => "required|unique:products,sku,".$Product->id,
            "description"   => "required",
            "product_image"    => "nullable|array",
            "product_variant"    => "required|array",
            "product_variant_prices"    => "required|array"
        );

        $validator = Validator::make( $request->all(), $rules);

        if ( $validator->fails() ){
            return [
                'success' => false,
                'message' => 'Validation failed !',
                'errors' =>  $validator->errors()
            ];
        }

        try {
            DB::beginTransaction();
                $Product->update([
                    'title' => $request->title,
                    'sku' => $request->sku,
                    'description' => $request->description,
                ]);
                $ProductVariant = Collect($request->product_variant);
                // return $ProductVariant;
                for ($i=0; $i < count($request->product_variant_prices); $i++) {
                    $ProductVariantDetails = $request->product_variant_prices[$i];
                    $Variants = explode('/',$ProductVariantDetails['title']);

                    $isProductPriceExits = ProductVariantPrice::findOrFail($request->product_variant_prices[$i]['id']);
                    if ($isProductPriceExits) {
                        $ProductVariantPrice  = $isProductPriceExits;
                    } else {
                        $ProductVariantPrice = new ProductVariantPrice;
                    }

                    foreach($Variants as $key => $VariantValue) {
                        foreach($ProductVariant as $VariantOption) {
                            if(in_array($VariantValue,$VariantOption['tags'])){
                                $ProductVainetValue = ProductVariant::create([
                                    'variant' => $VariantValue,
                                    'variant_id' => $VariantOption['option'],
                                    'product_id' =>$Product->id
                                ]);

                                if($key == 0) {
                                    $ProductVariantPrice->product_variant_one = $ProductVainetValue->id;
                                } else if ( $key == 1 ) {
                                    $ProductVariantPrice->product_variant_two = $ProductVainetValue->id;
                                } else if ( $key == 2 ) {
                                    $ProductVariantPrice->product_variant_three = $ProductVainetValue->id;
                                }

                            }
                        }
                    }
                    $ProductVariantPrice->product_id = $Product->id;
                    $ProductVariantPrice->price = $ProductVariantDetails['price'];
                    $ProductVariantPrice->stock = $ProductVariantDetails['stock'];
                    $Product->images()->save($ProductVariantPrice);
                }
                for ($i=0; $i < count($request->product_image); $i++) {
                    if(!$Product->images()->where('file_path',$request->product_image[$i])->first()){
                        $Product->images()->create([
                            'file_path' => $request->product_image[$i],
                            'thumbnail' => ($i== 0) ? 1 : 0
                        ]);
                    }
                }
            DB::commit();
            return [
                'success' => true,
                'message' => 'Product has been updated successfully !'
            ];
        } catch (\Throwable $th) {
            DB::rollBack(); dd($th);
            return [
                'success' => false,
                'message' => 'Somethings wrong !'
            ];
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }

    public function storeImage(Request $request) {
        if($request->hasFile('file')){
            if(!Storage::exists('product_images')){
                Storage::makeDirectory('product_images',777);
            }
            $file_name = 'product_images/'.rand().time().'.'.$request->file->getClientOriginalExtension();
            Storage::disk('public')->put($file_name , file_get_contents($request->file));
            return  $file_name ;
        }
    }
}
