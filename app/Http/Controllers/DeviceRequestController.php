<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceRequest;
use App\Models\Device;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Mail\StockActivityMail;
use App\Helpers\SmsHelper;
use App\Services\InventoryMovementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class DeviceRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->branch_id) {
            return redirect()->route('sales.index')
                ->withErrors(['branch' => 'You must be assigned to a branch to view device requests.']);
        }

        $tab = $request->get('tab', 'outgoing');

        $outgoingQuery = DeviceRequest::with(['device.product', 'device.branch', 'requestingBranch', 'requestedByUser', 'approvedByUser', 'rejectedByUser'])
            ->where('requesting_branch_id', $user->branch_id);

        $incomingQuery = DeviceRequest::with(['device.product', 'device.branch', 'requestingBranch', 'requestedByUser'])
            ->where('status', DeviceRequest::STATUS_PENDING)
            ->whereHas('device', fn($q) => $q->where('branch_id', $user->branch_id));

        if ($request->filled('status')) {
            $status = $request->get('status');
            $outgoingQuery->where('status', $status);
            if ($status === DeviceRequest::STATUS_PENDING) {
                $incomingQuery->where('status', $status);
            }
        }

        $outgoing = $outgoingQuery->latest()->paginate(10, ['*'], 'outgoing_page')->withQueryString();
        $incoming = $incomingQuery->latest()->paginate(10, ['*'], 'incoming_page')->withQueryString();

        $stats = [
            'outgoing_pending' => DeviceRequest::where('requesting_branch_id', $user->branch_id)->where('status', DeviceRequest::STATUS_PENDING)->count(),
            'outgoing_approved' => DeviceRequest::where('requesting_branch_id', $user->branch_id)->where('status', DeviceRequest::STATUS_APPROVED)->count(),
            'outgoing_rejected' => DeviceRequest::where('requesting_branch_id', $user->branch_id)->where('status', DeviceRequest::STATUS_REJECTED)->count(),
            'incoming_pending' => DeviceRequest::where('status', DeviceRequest::STATUS_PENDING)->whereHas('device', fn($q) => $q->where('branch_id', $user->branch_id))->count(),
        ];

        return view('device-requests.index', compact('outgoing', 'incoming', 'stats', 'tab'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->branch_id) {
            return redirect()->back()->withErrors(['branch' => 'You must be assigned to a branch to request a device.']);
        }

        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $device = Device::with('branch')->findOrFail($validated['device_id']);
        if ((string) $device->branch_id === (string) $user->branch_id) {
            return redirect()->back()->withErrors(['device' => 'This device is already in your branch.']);
        }
        if (!$device->isAvailable()) {
            return redirect()->back()->withErrors(['device' => 'This device is not available for request (may be sold or assigned).']);
        }

        $existing = DeviceRequest::where('device_id', $device->id)
            ->where('requesting_branch_id', $user->branch_id)
            ->where('status', DeviceRequest::STATUS_PENDING)
            ->first();
        if ($existing) {
            return redirect()->back()->withErrors(['device' => 'You already have a pending request for this device.']);
        }

        $deviceRequest = DeviceRequest::create([
            'device_id' => $device->id,
            'requesting_branch_id' => $user->branch_id,
            'requested_by' => $user->id,
            'status' => DeviceRequest::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
        ]);

        $deviceRequest->load(['device.product', 'device.branch', 'requestingBranch', 'requestedByUser']);
        $hostBranchId = $device->branch_id;
        $requestingBranchName = $user->branch->name ?? 'Another branch';
        $deviceLabel = $device->imei . ($device->product ? ' (' . $device->product->name . ')' : '');
        $title = 'Device request from ' . $requestingBranchName;
        $message = "{$requestingBranchName} has requested device {$deviceLabel}. Approve or reject the request.";
        $url = route('device-requests.show', $deviceRequest);

        $hostUsers = User::usersWhoCanViewDeviceRequests([$hostBranchId]);
        if ($hostUsers->isNotEmpty()) {
            Notification::send($hostUsers, new AppNotification($title, $message, $url, 'device_request', [
                'device_request_id' => $deviceRequest->id,
                'device_id' => $device->id,
                'requesting_branch_id' => $user->branch_id,
                'host_branch_id' => $hostBranchId,
            ]));
            $emails = $hostUsers->pluck('email')->filter()->unique()->values()->all();
            if (!empty($emails)) {
                Mail::to($emails)->queue(new StockActivityMail($title, $message, $url, 'View device request'));
            }
            $smsText = "Device request: {$requestingBranchName} requested device {$deviceLabel}. Log in to approve or reject.";
            foreach ($hostUsers as $hostUser) {
                if (!empty($hostUser->phone)) {
                    SmsHelper::send($hostUser->phone, $smsText);
                }
            }
        }

        return redirect()->route('device-requests.index', ['tab' => 'outgoing'])
            ->with('success', 'Device request sent. The host branch can approve or reject it.');
    }

    public function show(DeviceRequest $deviceRequest)
    {
        $user = Auth::user();
        if (!$user->branch_id) {
            abort(403, 'You must be assigned to a branch.');
        }
        $isRequesting = (string) $deviceRequest->requesting_branch_id === (string) $user->branch_id;
        $isHost = $deviceRequest->device && (string) $deviceRequest->device->branch_id === (string) $user->branch_id;
        if (!$isRequesting && !$isHost) {
            abort(403, 'You do not have access to this device request.');
        }

        $deviceRequest->load(['device.product', 'device.branch', 'requestingBranch', 'requestedByUser', 'approvedByUser', 'rejectedByUser']);
        return view('device-requests.show', compact('deviceRequest', 'isHost'));
    }

    public function approve(Request $request, DeviceRequest $deviceRequest)
    {
        $user = Auth::user();
        if (!$user->branch_id) {
            return redirect()->back()->withErrors(['branch' => 'You must be assigned to a branch.']);
        }
        if ($deviceRequest->status !== DeviceRequest::STATUS_PENDING) {
            return redirect()->back()->withErrors(['request' => 'This request is no longer pending.']);
        }
        if (!$deviceRequest->device || (string) $deviceRequest->device->branch_id !== (string) $user->branch_id) {
            abort(403, 'Only the branch that owns the device can approve this request.');
        }

        $device = $deviceRequest->device;
        $fromBranchId = $device->branch_id;
        $toBranchId = $deviceRequest->requesting_branch_id;
        $productId = $device->product_id;

        DB::transaction(function () use ($deviceRequest, $device, $fromBranchId, $toBranchId, $productId, $user) {
            $device->update(['branch_id' => $toBranchId]);

            InventoryMovementService::record(
                $fromBranchId,
                $productId,
                'transfer',
                -1,
                DeviceRequest::class,
                $deviceRequest->id,
                'Device request approved – outgoing',
                null,
                $user->id
            );

            InventoryMovementService::record(
                $toBranchId,
                $productId,
                'transfer',
                1,
                DeviceRequest::class,
                $deviceRequest->id,
                'Device request approved – incoming',
                null,
                $user->id
            );

            $deviceRequest->update([
                'status' => DeviceRequest::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        });

        $deviceRequest->load(['device.product', 'requestingBranch']);
        $device = $deviceRequest->device;
        $deviceLabel = $device ? $device->imei . ($device->product ? ' (' . $device->product->name . ')' : '') : 'device';
        $approverBranchName = $user->branch->name ?? 'Host branch';
        $title = 'Device request approved';
        $message = "Your request for {$deviceLabel} has been approved by {$approverBranchName}. The device is now in your branch.";
        $url = route('device-requests.show', $deviceRequest);

        $requestorUsers = User::usersWhoCanViewDeviceRequests([$deviceRequest->requesting_branch_id]);
        if ($requestorUsers->isNotEmpty()) {
            Notification::send($requestorUsers, new AppNotification($title, $message, $url, 'device_request', [
                'device_request_id' => $deviceRequest->id,
                'device_id' => $deviceRequest->device_id,
                'requesting_branch_id' => $deviceRequest->requesting_branch_id,
            ]));
            $emails = $requestorUsers->pluck('email')->filter()->unique()->values()->all();
            if (!empty($emails)) {
                Mail::to($emails)->queue(new StockActivityMail($title, $message, $url, 'View device request'));
            }
            $smsText = "Your device request for {$deviceLabel} was approved. The device is now in your branch.";
            foreach ($requestorUsers as $requestorUser) {
                if (!empty($requestorUser->phone)) {
                    SmsHelper::send($requestorUser->phone, $smsText);
                }
            }
        }

        return redirect()->route('device-requests.index', ['tab' => 'incoming'])
            ->with('success', 'Device request approved. The device has been moved to the requesting branch.');
    }

    public function reject(Request $request, DeviceRequest $deviceRequest)
    {
        $user = Auth::user();
        if (!$user->branch_id) {
            return redirect()->back()->withErrors(['branch' => 'You must be assigned to a branch.']);
        }
        if ($deviceRequest->status !== DeviceRequest::STATUS_PENDING) {
            return redirect()->back()->withErrors(['request' => 'This request is no longer pending.']);
        }
        if (!$deviceRequest->device || (string) $deviceRequest->device->branch_id !== (string) $user->branch_id) {
            abort(403, 'Only the branch that owns the device can reject this request.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $deviceRequest->update([
            'status' => DeviceRequest::STATUS_REJECTED,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return redirect()->route('device-requests.index', ['tab' => 'incoming'])
            ->with('success', 'Device request rejected.');
    }
}
