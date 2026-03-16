<?php

use App\Mail\StockActivityMail;
use App\Models\DeviceRequest;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * One-time backfill: send in-app and email notifications to host branch users
 * for existing PENDING device requests that were created before notifications were added.
 *
 * Safe for production:
 * - Only processes PENDING requests (host branch still needs to act).
 * - Processes in chunks to avoid memory/timeout.
 * - One failing request does not stop the rest (try-catch per request).
 * - No schema changes; read-only except notifications table and mail queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        $chunkSize = 50;
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        DeviceRequest::query()
            ->where('status', DeviceRequest::STATUS_PENDING)
            ->with(['device.product', 'device.branch', 'requestingBranch', 'requestedByUser'])
            ->chunk($chunkSize, function ($requests) use (&$sent, &$skipped, &$failed) {
                foreach ($requests as $deviceRequest) {
                    try {
                        $device = $deviceRequest->device;
                        if (!$device || !$device->branch_id) {
                            $skipped++;
                            continue;
                        }

                        $hostBranchId = $device->branch_id;
                        $requestingBranchName = $deviceRequest->requestingBranch->name ?? 'Another branch';
                        $deviceLabel = $device->imei . ($device->product ? ' (' . $device->product->name . ')' : '');
                        $title = 'Device request from ' . $requestingBranchName;
                        $message = "{$requestingBranchName} has requested device {$deviceLabel}. Approve or reject the request.";
                        $url = route('device-requests.show', $deviceRequest);

                        $hostUsers = User::usersWhoCanViewDeviceRequests([$hostBranchId]);
                        if ($hostUsers->isEmpty()) {
                            $skipped++;
                            continue;
                        }

                        Notification::send($hostUsers, new AppNotification($title, $message, $url, 'device_request', [
                            'device_request_id' => $deviceRequest->id,
                            'device_id' => $device->id,
                            'requesting_branch_id' => $deviceRequest->requesting_branch_id,
                            'host_branch_id' => $hostBranchId,
                        ]));

                        $emails = $hostUsers->pluck('email')->filter()->unique()->values()->all();
                        if (!empty($emails)) {
                            Mail::to($emails)->queue(new StockActivityMail($title, $message, $url, 'View device request'));
                        }

                        $sent++;
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::warning('Backfill device request notification failed', [
                            'device_request_id' => $deviceRequest->id ?? null,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            });

        if ($sent > 0 || $skipped > 0 || $failed > 0) {
            Log::info('Device request notification backfill completed', [
                'notifications_sent' => $sent,
                'skipped' => $skipped,
                'failed' => $failed,
            ]);
        }
    }

    /**
     * No-op: we cannot "unsend" notifications; down() is a no-op for data backfills.
     */
    public function down(): void
    {
        // Intentional no-op. Notifications and queued emails cannot be reverted.
    }
};
