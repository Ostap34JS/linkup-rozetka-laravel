@extends('layouts.app')

@section('title', $product->title)

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <slider :slides="{{ $product->images->pluck('original') }}"></slider>
            </div>
            <div class="col-md-4 showcase">
                <div class="pull-left shoe-name">
                    <h3>{{ $product->title }}</h3>
                    <p>
                        @foreach($product->categories as $category)
                            <a href="{{ route('categories.show', ['id' => $category->id]) }}" class="badge badge-primary">
                                {{ $category->title }}
                            </a>
                        @endforeach
                    </p>
                    <h4>{{ $product->price }} ГРН</h4>
                </div>
                <hr class="featurette-divider">
                <div class="form-group row">
                    <div class="col-12 pt-2">
                        <a href="" class="btn btn-block btn-outline-primary btn-lg">Add To Cart</a>
                    </div>
                    <div class="col-12 pt-2">
                        <a href="" class="btn btn-block btn-dark btn-lg">Buy Now</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 mt-5">
                <h3 class="text-uppercase">product details</h3>
                <article class="pl-3">
                    {!! $product->description !!}
                </article>
            </div>
        </div>
    </div>
@endsection