<?php

use App\Helpers\SmsHelper;
use App\Models\DeviceRequest;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * One-time backfill: send SMS notifications to host branch users for existing
 * PENDING device requests (e.g. requests created before SMS was added).
 *
 * Safe for production:
 * - Only processes PENDING requests.
 * - Chunks to avoid memory/timeout.
 * - One failing request does not stop the rest (try-catch per request).
 * - No schema changes; only sends SMS via gateway.
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
            ->with(['device.product', 'device.branch', 'requestingBranch'])
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
                        $smsText = "Device request: {$requestingBranchName} requested device {$deviceLabel}. Log in to approve or reject.";

                        $hostUsers = User::usersWhoCanViewDeviceRequests([$hostBranchId]);
                        if ($hostUsers->isEmpty()) {
                            $skipped++;
                            continue;
                        }

                        $smsSent = 0;
                        foreach ($hostUsers as $hostUser) {
                            if (!empty($hostUser->phone)) {
                                SmsHelper::send($hostUser->phone, $smsText);
                                $smsSent++;
                            }
                        }
                        if ($smsSent > 0) {
                            $sent++;
                        } else {
                            $skipped++;
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::warning('Backfill device request SMS failed', [
                            'device_request_id' => $deviceRequest->id ?? null,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            });

        if ($sent > 0 || $skipped > 0 || $failed > 0) {
            Log::info('Device request SMS backfill completed', [
                'requests_notified' => $sent,
                'skipped' => $skipped,
                'failed' => $failed,
            ]);
        }
    }

    /**
     * No-op: we cannot "unsend" SMS.
     */
    public function down(): void
    {
        // Intentional no-op.
    }
};
