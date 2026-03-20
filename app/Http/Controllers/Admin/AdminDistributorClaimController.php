<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistributorClaim;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;

class AdminDistributorClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = DistributorClaim::with(['distributorProfile.customer', 'distributorProfile.user'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $claims = $query->paginate(20)->withQueryString();

        return view('admin.distributor-portal.claims.index', compact('claims'));
    }

    public function show(DistributorClaim $claim)
    {
        $claim->load(['distributorProfile.customer', 'distributorProfile.user', 'referenceSale', 'attachments', 'reviewer']);
        return view('admin.distributor-portal.claims.show', compact('claim'));
    }

    public function approve(Request $request, DistributorClaim $claim)
    {
        $request->validate([
            'amount_approved' => 'required|numeric|min:0',
            'reviewer_notes'  => 'nullable|string|max:1000',
        ]);

        $claim->update([
            'status'          => 'approved',
            'amount_approved' => $request->amount_approved,
            'reviewer_notes'  => $request->reviewer_notes,
            'reviewed_by'     => auth()->id(),
            'reviewed_at'     => now(),
        ]);

        // Notify the distributor
        $distributorUser = $claim->distributorProfile?->user;
        if ($distributorUser) {
            $distributorUser->notify(new AppNotification(
                title: 'Claim Approved',
                message: "Your claim {$claim->claim_number} has been approved. Amount: " . number_format($request->amount_approved, 2) . '.',
                actionUrl: route('portal.claims.show', $claim),
                type: 'distributor_claim',
            ));
        }

        return redirect()->route('admin.distributor-portal.claims.show', $claim)
            ->with('success', 'Claim approved successfully.');
    }

    public function reject(Request $request, DistributorClaim $claim)
    {
        $request->validate([
            'reviewer_notes' => 'required|string|min:5|max:1000',
        ]);

        $claim->update([
            'status'         => 'rejected',
            'reviewer_notes' => $request->reviewer_notes,
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
        ]);

        // Notify the distributor
        $distributorUser = $claim->distributorProfile?->user;
        if ($distributorUser) {
            $distributorUser->notify(new AppNotification(
                title: 'Claim Rejected',
                message: "Your claim {$claim->claim_number} has been reviewed and rejected.",
                actionUrl: route('portal.claims.show', $claim),
                type: 'distributor_claim',
            ));
        }

        return redirect()->route('admin.distributor-portal.claims.show', $claim)
            ->with('success', 'Claim rejected.');
    }
}
