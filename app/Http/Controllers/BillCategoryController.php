<?php

namespace App\Http\Controllers;

use App\Models\BillCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = BillCategory::query()->orderBy('name');

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        if ($request->filled('active')) {
            if ($request->get('active') === '1') {
                $query->where('is_active', true);
            } elseif ($request->get('active') === '0') {
                $query->where('is_active', false);
            }
        }

        $categories = $query->paginate(20)->withQueryString();

        return view('bills.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('bills.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'is_active' => 'boolean',
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        // Ensure slug is unique
        $baseSlug = $validated['slug'];
        $count = 0;
        while (BillCategory::where('slug', $validated['slug'])->exists()) {
            $count++;
            $validated['slug'] = $baseSlug . '-' . $count;
        }

        BillCategory::create($validated);

        return redirect()->route('bills.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(BillCategory $category)
    {
        return view('bills.categories.edit', compact('category'));
    }

    public function update(Request $request, BillCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'is_active' => 'boolean',
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        // Ensure slug is unique (excluding current)
        $baseSlug = $validated['slug'];
        $count = 0;
        while (BillCategory::where('slug', $validated['slug'])->where('id', '!=', $category->id)->exists()) {
            $count++;
            $validated['slug'] = $baseSlug . '-' . $count;
        }

        $category->update($validated);

        return redirect()->route('bills.categories.index')
            ->with('success', 'Category updated successfully.');
    }
}
