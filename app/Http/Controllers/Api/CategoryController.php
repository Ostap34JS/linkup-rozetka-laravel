<?php

namespace App\Http\Controllers\Api;

use App\Category;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function show($id)
    {
        $products = Category::find($id)->products()->paginate();

        return response()->json($products);
    }
}
