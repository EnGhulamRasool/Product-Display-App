<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Events\ProductUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('id', 'asc')->paginate(10);
        return view('products', compact('products'));
    }

    public function fetchProducts()
    {
        $response = Http::get('https://fakestoreapi.com/products');

        if ($response->successful()) {
            $apiProducts = $response->json();

            collect($apiProducts)->chunk(5)->each(function ($chunk) {
                foreach ($chunk as $apiProduct) {
                    $product = Product::updateOrCreate(
                        ['name' => $apiProduct['title']],
                        [
                            'description' => $apiProduct['description'],
                            'price' => $apiProduct['price'],
                        ]
                    );
                    broadcast(new ProductUpdated($product))->toOthers();
                }
            });

            return response()->json(['success' => 'Products fetched successfully!']);
        }

        return response()->json(['error' => 'Failed to fetch products'], 500);
    }
}
