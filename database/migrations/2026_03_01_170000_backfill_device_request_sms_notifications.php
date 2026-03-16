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
        // No-op: Device and DeviceRequest models removed; backfill no longer applicable.
    }

    /**
     * No-op: we cannot "unsend" SMS.
     */
    public function down(): void
    {
        // Intentional no-op.
    }
};
