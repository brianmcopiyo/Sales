<?php

namespace App\Notifications;

use App\Models\DistributorClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewDistributorClaim extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DistributorClaim $claim) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'      => 'New Distributor Claim',
            'message'    => "Claim {$this->claim->claim_number} submitted by {$this->claim->distributorProfile?->customer?->name}. Type: {$this->claim->getTypeLabel()}.",
            'action_url' => route('admin.distributor-portal.claims.show', $this->claim),
            'activity'   => 'distributor_claim',
            'type'       => 'distributor_claim',
        ];
    }
}
