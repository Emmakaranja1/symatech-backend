<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Spatie\Activitylog\Facades\Activity;       


class ProductController extends Controller
{
    // Public products API
    public function publicIndex()
    {
        $products = Product::where('active', true)
            ->select([
                'id', 
                'title', 
                'category', 
                'price', 
                'stock', 
                'description', 
                'image', 
                'rating'
            ])
            ->get()
            ->map(function ($product) {
                // Handle image URL
                $imageUrl = $product->image;
                if ($imageUrl) {
                    // If it's not a full URL, make it one
                    if (!str_starts_with($imageUrl, 'http')) {
                        $imageUrl = url('storage/' . $imageUrl);
                    }
                    // Fix escaped slashes
                    $imageUrl = str_replace('\\/', '/', $imageUrl);
                } else {
                    // Provide a placeholder image for products without images
                    $imageUrl = 'https://picsum.photos/seed/product-' . $product->id . '/400/300.jpg';
                }

                return [
                    'id' => $product->id,
                    'title' => $product->title ?: $product->name,
                    'category' => $product->category ?: 'General',
                    'price' => (float) $product->price,
                    'stock' => $product->stock,
                    'description' => $product->description ?: 'No description available',
                    'image' => $imageUrl,
                    'rating' => (float) $product->rating,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Products retrieved successfully'
        ], 200);
    }

    // Admin products API
    public function adminIndex()
    {
        $products = Product::select([
            'id', 
            'name', 
            'sku', 
            'category', 
            'price', 
            'cost_price', 
            'stock', 
            'weight', 
            'dimensions', 
            'description', 
            'images', 
            'active', 
            'featured', 
            'status'
        ])
        ->get()
        ->map(function ($product) {
            // Handle images array
            $images = $product->images ?? [];
            $processedImages = [];
            foreach ($images as $image) {
                if ($image) {
                    // Fix escaped slashes and ensure full URL
                    $imageUrl = str_replace('\\/', '/', $image);
                    if (!str_starts_with($imageUrl, 'http')) {
                        $imageUrl = url('storage/' . $imageUrl);
                    }
                    $processedImages[] = $imageUrl;
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category,
                'price' => $product->formatted_price,
                'costPrice' => $product->formatted_cost_price,
                'stock' => $product->stock,
                'weight' => $product->weight,
                'dimensions' => $product->dimensions,
                'description' => $product->description,
                'active' => $product->active,
                'featured' => $product->featured,
                'status' => $product->status,
                'images' => $processedImages,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products
            ],
            'message' => 'Admin products retrieved successfully'
        ], 200);
    }

    // List all products
    public function index()
    {
        return response()->json(Product::all(), 200);
    }

    // Show single product
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Handle image URL
        $imageUrl = $product->image;
        if ($imageUrl) {
            // Fix escaped slashes and ensure full URL
            $imageUrl = str_replace('\\/', '/', $imageUrl);
            if (!str_starts_with($imageUrl, 'http')) {
                $imageUrl = url('storage/' . $imageUrl);
            }
        } else {
            // Provide a placeholder image
            $imageUrl = 'https://picsum.photos/seed/product-' . $product->id . '/400/300.jpg';
        }

        // Handle images array
        $images = $product->images ?? [];
        $processedImages = [];
        foreach ($images as $image) {
            if ($image) {
                $imageUrl = str_replace('\\/', '/', $image);
                if (!str_starts_with($imageUrl, 'http')) {
                    $imageUrl = url('storage/' . $imageUrl);
                }
                $processedImages[] = $imageUrl;
            }
        }

        $productData = [
            'id' => $product->id,
            'name' => $product->name,
            'title' => $product->title ?: $product->name,
            'sku' => $product->sku,
            'category' => $product->category,
            'price' => (float) $product->price,
            'cost_price' => (float) $product->cost_price,
            'stock' => $product->stock,
            'weight' => $product->weight,
            'dimensions' => $product->dimensions,
            'description' => $product->description,
            'image' => $imageUrl,
            'images' => $processedImages,
            'rating' => (float) $product->rating,
            'active' => $product->active,
            'featured' => $product->featured,
            'status' => $product->status,
        ];

        return response()->json($productData, 200);
    }
// Store new product
public function store(Request $request)
{
    $user = auth()->user();
    if (!$user) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'stock' => 'required|integer|min:0',
        'category' => 'required|string|max:255',
        'image' => 'nullable|url',
        'images' => 'nullable|array',
        'images.*' => 'nullable|url',
        'rating' => 'nullable|numeric|min:0|max:5',
    ]);

    \DB::beginTransaction();
    try {
        $product = Product::create($validated);
        
        activity()
            ->causedBy($user)
            ->performedOn($product)
            ->withProperties(['attributes' => $validated])
            ->log('created');
            
        \DB::commit();
        
        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
        
    } catch (\Exception $e) {
        \DB::rollBack();
        \Log::error('Error creating product: ' . $e->getMessage());
        return response()->json(['error' => 'Error creating product'], 500);
    }
}

    // Update product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'category' => 'sometimes|required|string|max:255',
            'image' => 'nullable|url',
            'images' => 'nullable|array',
            'images.*' => 'nullable|url',
            'rating' => 'nullable|numeric|min:0|max:5',
        ]);

        $product->update($validated);

        // Log update
        activity()
            ->causedBy(auth()->user())
            ->performedOn($product)
            ->withProperties($validated)
            ->log('Product updated');

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ], 200);
    }

    // Delete product
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Optional: Log deletion BEFORE deleting
        activity()
            ->causedBy(auth()->user())
            ->performedOn($product)
            ->log('Product deleted');

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ], 200);
    }
}


