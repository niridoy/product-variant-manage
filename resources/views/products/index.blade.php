@extends('layouts.app')

<style>
    .pagination {
        float: right;
    }
</style>

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" class="form-control">
                </div>
                <div class="col-md-2 d-flex">
                    <select name="variant" id="" style="width: 100%" class="form-control">
                        <option value="">Select Variant</option>
                        @foreach ($ProductVariantNames as $ProductVariant)
                            <option name="{{ $ProductVariant->variant }}" id=""> {{ $ProductVariant->variant }} </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" aria-label="First name" placeholder="From" class="form-control">
                        <input type="text" name="price_to" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                        @foreach ($Products as $Product)
                            <tr>
                                <td>{{ $Product->id }}</td>
                                <td>{{ $Product->title }}<br> Created at : {{ $Product->formated_created_date }} </td>
                                <td width="40%">{{ $Product->description }}</td>
                                <td>

                                        <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant{{ $Product->id }}">
                                            @foreach ($Product->variants as $variant)
                                                <dt class="col-sm-3 pb-0">
                                                    {{ $variant->one->variant }} / {{ $variant->two->variant }} / {{ $variant->three->variant  }}
                                                </dt>
                                            <dd class="col-sm-9">
                                                <dl class="row mb-0">
                                                    <dt class="col-sm-4 pb-0">Price : {{ number_format($variant->price,2) }}</dt>
                                                    <dd class="col-sm-8 pb-0">InStock : {{ $variant->stock }}</dd>
                                                </dl>
                                            </dd>
                                            @endforeach
                                        </dl>
                                    <button onclick="$('#variant{{ $Product->id }}').toggleClass('h-auto')" class="btn btn-sm btn-link">Show more</button>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('product.edit', $Product->id) }}" class="btn btn-success">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-4">
                    <p>Showing {{ $Products->currentPage() * 2 - 1   }} to {{ $Products->currentPage() * 2 }}  out of  {{ $Products->total() }} </p>
                </div>
                <div class="col-md-8 text-right">
                    {{ $Products->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
