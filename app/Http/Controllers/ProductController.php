<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Region;
use App\Exports\ProductsExport;
use App\Services\ProductMergeService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $regionId = auth()->user()?->branch?->region_id;

        $query = Product::with([
            'brand',
            'regionPrices' => fn($q) => $regionId ? $q->where('region_id', $regionId) : $q->whereRaw('1=0'),
        ]);

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('model', 'like', "%{$term}%");
            });
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->get('brand_id'));
        }
        if ($request->filled('status')) {
            if ($request->get('status') === 'active') {
                $query->where('is_active', true);
            }
            if ($request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $products = $query->latest()->paginate(15)->withQueryString();

        $lowStockProducts = Product::with('branchStocks')->get()->filter(function ($product) {
            $minimumLevel = $product->minimum_stock_level ?? 10;
            return $product->branchStocks->contains(function ($stock) use ($minimumLevel) {
                return $stock->quantity <= $minimumLevel;
            });
        });

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'low_stock' => $lowStockProducts->count(),
        ];

        $brands = Brand::orderBy('name')->get(['id', 'name']);

        return view('products.index', compact('products', 'stats', 'brands'));
    }

    public function export(Request $request)
    {
        $filename = 'products-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new ProductsExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function create()
    {
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        return view('products.create', compact('brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:products,sku',
            'description' => 'nullable|string',
            'brand_id' => 'required|exists:brands,id',
            'model' => 'nullable|string|max:255',
            // Pricing is managed per-region under Pricing
            'minimum_stock_level' => 'nullable|integer|min:0',
            'license_cost' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load(['brand', 'regionPrices.region']);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        return view('products.edit', compact('product', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'brand_id' => 'required|exists:brands,id',
            'model' => 'nullable|string|max:255',
            // Pricing is managed per-region under Pricing
            'minimum_stock_level' => 'nullable|integer|min:0',
            'license_cost' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    /**
     * Show the merge form.
     * - With recipient_id: this product is the target; user selects which other products to merge into it.
     * - With ids[]: user selected multiple products; user chooses which one is the recipient.
     */
    public function mergeForm(Request $request)
    {
        $recipientId = $request->input('recipient_id');
        if ($recipientId) {
            $recipient = Product::with('brand')->findOrFail($recipientId);
            $sources = Product::with('brand')->where('id', '!=', $recipientId)->orderBy('name')->get();
            return view('products.merge', [
                'recipient' => $recipient,
                'sources' => $sources,
                'recipientFixed' => true,
            ]);
        }
        $request->validate(['ids' => 'required|array', 'ids.*' => 'uuid|exists:products,id']);
        $ids = array_values(array_unique($request->input('ids')));
        if (count($ids) < 2) {
            return redirect()->route('products.index')->with('error', 'Select at least 2 products to merge.');
        }
        $products = Product::with('brand')->whereIn('id', $ids)->orderBy('name')->get();
        return view('products.merge', [
            'products' => $products,
            'recipientFixed' => false,
        ]);
    }

    /**
     * Perform the merge: transfer all devices and sales from source products into the target product.
     */
    public function merge(Request $request, ProductMergeService $mergeService)
    {
        $validated = $request->validate([
            'target_id' => 'required|uuid|exists:products,id',
            'ids' => 'required_without:source_ids|array',
            'ids.*' => 'uuid|exists:products,id',
            'source_ids' => 'required_without:ids|array',
            'source_ids.*' => 'uuid|exists:products,id',
        ]);
        $targetId = $validated['target_id'];
        if (!empty($validated['source_ids'])) {
            $sourceIds = array_values(array_unique($validated['source_ids']));
            $sourceIds = array_values(array_diff($sourceIds, [$targetId]));
        } else {
            $allIds = array_values(array_unique($validated['ids']));
            $sourceIds = array_values(array_diff($allIds, [$targetId]));
        }
        if (empty($sourceIds)) {
            return redirect()->route('products.index')->with('error', 'Select at least one other product to merge into the recipient.');
        }
        try {
            $mergeService->merge($sourceIds, $targetId);
            return redirect()->route('products.index')->with('success', 'Products merged successfully. All sales have been transferred to the selected product.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }
    }
}
