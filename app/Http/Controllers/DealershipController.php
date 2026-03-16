<?php

namespace App\Http\Controllers;

use App\Models\Dealership;
use Illuminate\Http\Request;

class DealershipController extends Controller
{
    public function index(Request $request)
    {
        $query = Dealership::query()->orderBy('name');

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        }

        $dealerships = $query->paginate(20)->withQueryString();

        return view('dealerships.index', compact('dealerships'));
    }

    public function create()
    {
        return view('dealerships.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:64|unique:dealerships,code',
        ]);

        Dealership::create($validated);

        return redirect()->route('dealerships.index')
            ->with('success', 'Dealership created successfully.');
    }

    public function edit(Dealership $dealership)
    {
        return view('dealerships.edit', compact('dealership'));
    }

    public function update(Request $request, Dealership $dealership)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:64|unique:dealerships,code,' . $dealership->id,
        ]);

        $dealership->update($validated);

        return redirect()->route('dealerships.index')
            ->with('success', 'Dealership updated successfully.');
    }
}
