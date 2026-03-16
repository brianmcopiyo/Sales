<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query()->orderBy('name');

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('contact_person', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('active')) {
            if ($request->get('active') === '1') {
                $query->where('is_active', true);
            } elseif ($request->get('active') === '0') {
                $query->where('is_active', false);
            }
        }

        $vendors = $query->paginate(20)->withQueryString();

        return view('bills.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('bills.vendors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:120',
            'phone' => 'nullable|string|max:40',
            'address' => 'nullable|string',
            'default_payment_terms' => 'nullable|string|in:net_30,due_on_receipt,custom',
            'terms_days' => 'nullable|integer|min:1|max:365',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $vendor = Vendor::create($validated);
        ActivityLog::log(
            auth()->id(),
            'vendor_created',
            "Vendor created: {$vendor->name}",
            Vendor::class,
            $vendor->id,
            ['name' => $vendor->name]
        );

        return redirect()->route('bills.vendors.index')
            ->with('success', 'Vendor created successfully.');
    }

    public function edit(Vendor $vendor)
    {
        return view('bills.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:120',
            'phone' => 'nullable|string|max:40',
            'address' => 'nullable|string',
            'default_payment_terms' => 'nullable|string|in:net_30,due_on_receipt,custom',
            'terms_days' => 'nullable|integer|min:1|max:365',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $vendor->update($validated);
        ActivityLog::log(
            auth()->id(),
            'vendor_updated',
            "Vendor updated: {$vendor->name}",
            Vendor::class,
            $vendor->id,
            ['name' => $vendor->name]
        );

        return redirect()->route('bills.vendors.index')
            ->with('success', 'Vendor updated successfully.');
    }
}
