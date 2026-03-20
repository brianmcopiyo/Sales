<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistributorProfile;
use App\Models\DistributorTarget;
use Illuminate\Http\Request;

class AdminDistributorTargetController extends Controller
{
    public function store(Request $request, DistributorProfile $profile)
    {
        $validated = $request->validate([
            'target_type'  => 'required|in:' . implode(',', array_keys(DistributorTarget::TARGET_TYPES)),
            'period_type'  => 'required|in:' . implode(',', array_keys(DistributorTarget::PERIOD_TYPES)),
            'period_year'  => 'required|integer|min:2020|max:2099',
            'period_value' => 'required|integer|min:1|max:12',
            'target_value' => 'required|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ]);

        $profile->targets()->create([
            ...$validated,
            'set_by' => auth()->id(),
        ]);

        return redirect()->route('admin.distributor-portal.show', $profile)
            ->with('success', 'Target set successfully.');
    }

    public function destroy(DistributorProfile $profile, DistributorTarget $target)
    {
        abort_if($target->distributor_profile_id !== $profile->id, 403);
        $target->delete();

        return redirect()->route('admin.distributor-portal.show', $profile)
            ->with('success', 'Target removed.');
    }
}
