<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use Illuminate\Http\Request;

class PortalInventoryController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;

        if (!$profile->assigned_branch_id) {
            return view('portal.inventory.index', [
                'stocks'  => collect(),
                'profile' => $profile,
                'noBranch' => true,
            ]);
        }

        $query = BranchStock::with('product.brand')
            ->where('branch_id', $profile->assigned_branch_id)
            ->orderBy('quantity');

        if ($request->filled('search')) {
            $query->whereHas('product', fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'));
        }

        if ($request->filled('low_stock')) {
            $query->where('quantity', '<=', \DB::raw('minimum_quantity'));
        }

        $stocks = $query->paginate(25)->withQueryString();

        return view('portal.inventory.index', compact('stocks', 'profile'));
    }
}
