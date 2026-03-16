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
        // No-op: Device and DeviceRequest models removed; backfill no longer applicable.
    }

    /**
     * No-op: we cannot "unsend" notifications; down() is a no-op for data backfills.
     */
    public function down(): void
    {
        // Intentional no-op. Notifications and queued emails cannot be reverted.
    }
};
