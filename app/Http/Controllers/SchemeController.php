<?php

namespace App\Http\Controllers;

use App\Models\Scheme;
use App\Models\Region;
use App\Models\Outlet;
use Illuminate\Http\Request;

class SchemeController extends Controller
{
    public function index(Request $request)
    {
        $query = Scheme::with('region');

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->get('is_active'));
        }
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->get('region_id'));
        }

        $schemes = $query->latest()->paginate(20)->withQueryString();

        $types = Scheme::types();
        $regions = Region::orderBy('name')->get(['id', 'name']);

        // Schemes applied today count
        $appliedToday = \Illuminate\Support\Facades\DB::table('sale_scheme')
            ->whereDate('created_at', today())
            ->count();

        $stats = [
            'active_count'   => Scheme::where('is_active', true)->count(),
            'applied_today'  => $appliedToday,
        ];

        return view('schemes.index', compact('schemes', 'types', 'regions', 'stats'));
    }

    public function create()
    {
        $types = Scheme::types();
        $regions = Region::orderBy('name')->get(['id', 'name']);
        $outletTypes = Outlet::types();
        return view('schemes.create', compact('types', 'regions', 'outletTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['applies_to_outlet_types'] = $request->input('applies_to_outlet_types') ?: null;
        Scheme::create($validated);
        return redirect()->route('schemes.index')->with('success', 'Scheme created.');
    }

    public function show(Scheme $scheme)
    {
        $scheme->load('region');
        $sales = $scheme->sales()->with(['outlet', 'soldBy'])->latest()->paginate(20);
        return view('schemes.show', compact('scheme', 'sales'));
    }

    public function edit(Scheme $scheme)
    {
        $types = Scheme::types();
        $regions = Region::orderBy('name')->get(['id', 'name']);
        $outletTypes = Outlet::types();
        return view('schemes.edit', compact('scheme', 'types', 'regions', 'outletTypes'));
    }

    public function update(Request $request, Scheme $scheme)
    {
        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['applies_to_outlet_types'] = $request->input('applies_to_outlet_types') ?: null;
        $scheme->update($validated);
        return redirect()->route('schemes.edit', $scheme)->with('success', 'Scheme updated.');
    }

    public function destroy(Scheme $scheme)
    {
        if ($scheme->sales()->exists()) {
            return redirect()->route('schemes.index')
                ->with('error', 'Cannot delete a scheme that has been applied to sales.');
        }
        $scheme->delete();
        return redirect()->route('schemes.index')->with('success', 'Scheme deleted.');
    }

    private function rules(): array
    {
        return [
            'name'                        => 'required|string|max:255',
            'description'                 => 'nullable|string|max:5000',
            'type'                        => 'required|in:flat_discount,percentage_discount,buy_x_get_y',
            'value'                       => 'required|numeric|min:0',
            'min_order_amount'            => 'nullable|numeric|min:0',
            'min_quantity'                => 'nullable|integer|min:1',
            'buy_quantity'                => 'nullable|integer|min:1',
            'get_quantity'                => 'nullable|integer|min:1',
            'start_date'                  => 'required|date',
            'end_date'                    => 'required|date|after_or_equal:start_date',
            'is_active'                   => 'boolean',
            'applies_to_outlet_types'     => 'nullable|array',
            'applies_to_outlet_types.*'   => 'in:retail,kiosk,dealer,wholesale,other',
            'region_id'                   => 'nullable|exists:regions,id',
        ];
    }
}
