<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DistributorClaim;
use App\Models\DistributorClaimAttachment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortalClaimController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;

        $query = $profile->claims()
            ->with(['attachments', 'referenceSale'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $claims = $query->paginate(15)->withQueryString();

        return view('portal.claims.index', compact('claims', 'profile'));
    }

    public function create(Request $request)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;

        // Recent sales for the reference dropdown
        $recentSales = Sale::where('customer_id', $profile->customer_id)
            ->secondarySales()
            ->latest()
            ->limit(30)
            ->get(['id', 'sale_number', 'created_at', 'total']);

        $preselectedSaleId = $request->get('sale');

        return view('portal.claims.create', compact('profile', 'recentSales', 'preselectedSaleId'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;

        $validated = $request->validate([
            'type'              => 'required|in:' . implode(',', array_keys(DistributorClaim::TYPES)),
            'description'       => 'required|string|min:10|max:2000',
            'amount_claimed'    => 'nullable|numeric|min:0',
            'reference_sale_id' => 'nullable|exists:sales,id',
            'attachments'       => 'nullable|array',
            'attachments.*'     => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Ensure the referenced sale belongs to this customer
        if (!empty($validated['reference_sale_id'])) {
            $sale = Sale::find($validated['reference_sale_id']);
            abort_if(!$sale || $sale->customer_id !== $profile->customer_id, 422, 'Invalid sale reference.');
        }

        $claim = DistributorClaim::create([
            'distributor_profile_id' => $profile->id,
            'type'                   => $validated['type'],
            'description'            => $validated['description'],
            'amount_claimed'         => $validated['amount_claimed'] ?? null,
            'reference_sale_id'      => $validated['reference_sale_id'] ?? null,
            'status'                 => 'pending',
        ]);

        // Store attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = Storage::disk('public')->putFile('distributor-claims', $file);
                DistributorClaimAttachment::create([
                    'claim_id'          => $claim->id,
                    'file_path'         => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type'         => $file->getMimeType(),
                    'uploaded_by'       => auth()->id(),
                ]);
            }
        }

        // Notify internal users with claims approval permission
        $notifyUsers = User::whereHas('roleModel.permissions', fn ($q) => $q->where('slug', 'distributor-portal.claims.approve'))->get();
        foreach ($notifyUsers as $adminUser) {
            $adminUser->notify(new \App\Notifications\NewDistributorClaim($claim));
        }

        return redirect()->route('portal.claims.show', $claim)
            ->with('success', "Claim {$claim->claim_number} submitted successfully. We will review it shortly.");
    }

    public function show(DistributorClaim $claim)
    {
        /** @var \App\Models\DistributorProfile $profile */
        $profile = auth()->user()->distributorProfile;

        abort_if($claim->distributor_profile_id !== $profile->id, 403, 'Access denied.');

        $claim->load(['attachments', 'referenceSale', 'reviewer']);

        return view('portal.claims.show', compact('claim', 'profile'));
    }
}
