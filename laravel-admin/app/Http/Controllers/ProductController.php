<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{


    public function index(Request $request)
    {
        $products = $this->getFilteredProducts($request);
        $categories = Category::all();

        return view('products.index', compact('products', 'categories'));
    }

    public function filterAndSearchProducts(Request $request)
    {
        $products = $this->getFilteredProducts($request);

        return view('products.filtered', compact('products'));
    }

    private function getFilteredProducts($request)
    {
        $products = Product::query();

        if (!empty($request->search)) {
            $products->where(function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%")
                    ->orWhere('code', 'like', "%$request->search%");
            });
        }

        if (!empty($request->price_range)) {
            $priceLimits = explode('-', $request->price_range);
            $minPrice = $priceLimits[0];
            $maxPrice = !empty($priceLimits[1]) ? $priceLimits[1] : PHP_FLOAT_MAX;
            $products->whereBetween('unit_price', [$minPrice, $maxPrice]);
        }

        if (!empty($request->category)) {
            $products->where('category_id', $request->category);
        }

        return $products->get();
    }


    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('add_products');

        

        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'required|string',
            'product_description' => 'nullable|string',
            'unit_price' => 'required|numeric',
            'discount' => 'required|numeric',
            'final_price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        $imagePath = null;
        if ($request->hasFile('product_image')) {
            $image = $request->file('product_image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads'), $imageName);
            $imagePath = 'uploads/' . $imageName;
        }
   
        $product = Product::create([
            'name' => $request->input('product_name'),
            'code' => $request->input('product_code'),
            'description' => $request->input('product_description'),
            'status' => $request->input('product_status'),
            'unit_price' => $request->input('unit_price'),
            'discount' => $request->input('discount'),
            'final_price' => $request->input('final_price'),
            'category_id' => $request->input('category_id'),
            'image' => $imagePath,
        ]);

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'product_code' => 'required|string|max:255',
            'unit_price' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'final_price' => 'nullable|numeric',
            'category_id' => 'required|exists:categories,id',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_description' => 'nullable|string',
        ]);


        if ($request->hasFile('product_image')) {
            if ($product->image) {
                Storage::delete($product->image);
            }
            $imagePath = $request->file('product_image')->store('products', 'public');
            $product->image = $imagePath;
        }

        $product->name = $request->input('product_name');
        $product->code = $request->input('product_code');
        $product->unit_price = $request->input('unit_price');
        $product->discount = $request->input('discount') ?? 0;
        $product->final_price = $request->input('final_price') ?? ($request->input('unit_price') - ($request->input('unit_price') * $request->input('discount') / 100));
        $product->category_id = $request->input('category_id');
        $product->description = $request->input('product_description');

        $product->save();

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }


    public function destroy(Product $product)
    {
        $this->authorize('delete_products', $product);

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}
