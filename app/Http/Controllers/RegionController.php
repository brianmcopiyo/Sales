<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Region;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        $query = Region::withCount('branches');

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }
        if ($request->filled('status')) {
            if ($request->get('status') === 'active') {
                $query->where('is_active', true);
            }
            if ($request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $regions = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => Region::count(),
            'active' => Region::where('is_active', true)->count(),
            'inactive' => Region::where('is_active', false)->count(),
        ];

        return view('regions.index', compact('regions', 'stats'));
    }

    public function create()
    {
        return view('regions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Region::create($validated);
        return redirect()->route('regions.index')->with('success', 'Region created successfully.');
    }

    public function show(Region $region)
    {
        $region->load('branches');
        return view('regions.show', compact('region'));
    }

    public function edit(Region $region)
    {
        return view('regions.edit', compact('region'));
    }

    public function update(Request $request, Region $region)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name,' . $region->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $region->update($validated);
        return redirect()->route('regions.index')->with('success', 'Region updated successfully.');
    }

    public function destroy(Region $region)
    {
        $region->delete();
        return redirect()->route('regions.index')->with('success', 'Region deleted successfully.');
    }
}
