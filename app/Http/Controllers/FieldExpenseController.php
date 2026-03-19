<?php

namespace App\Http\Controllers;

use App\Models\FieldExpense;
use App\Models\Outlet;
use Illuminate\Http\Request;

class FieldExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = FieldExpense::query()->with(['user', 'outlet']);
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->get('date_to'));
        }

        $rows = $query->latest('expense_date')->paginate(20)->withQueryString();
        return view('field-expenses.index', compact('rows'));
    }

    public function create()
    {
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        return view('field-expenses.create', compact('outlets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'nullable|exists:outlets,id',
            'expense_date' => 'required|date',
            'category' => 'required|string|max:64',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:8',
            'description' => 'nullable|string|max:2000',
        ]);

        FieldExpense::create([
            'user_id' => $request->user()->id,
            'outlet_id' => $validated['outlet_id'] ?? null,
            'expense_date' => $validated['expense_date'],
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'KES',
            'status' => 'submitted',
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('field-expenses.index')->with('success', 'Field expense submitted.');
    }
}
