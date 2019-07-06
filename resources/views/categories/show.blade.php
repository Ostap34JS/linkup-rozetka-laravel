@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Products from category "{{ $category->title }}"</h1>
        <products :category_id="{{ $category->id }}"></products>
    </div>
@endsection