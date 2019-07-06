@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Categories</h1>
        <div class="list-group">
            @foreach($categories as $category)
                <div class="list-group-item">
                    {{ $category->title }}
                </div>
                @foreach($category->children as $children)
                    <a href="{{ route('categories.show', ['id' => $children->id]) }}"
                       class="list-group-item list-group-item-action ml-5">
                        {{ $children->title }}
                    </a>
                @endforeach
            @endforeach
        </div>
    </div>
@endsection