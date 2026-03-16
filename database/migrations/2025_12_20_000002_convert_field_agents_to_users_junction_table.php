<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Existing installs might have field_agents as an independent table.
        // Convert it to a junction/profile table keyed by users.id.

        // 1) Remove FK from sale_items.field_agent_id (could point to field_agents or users)
        try {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropForeign(['field_agent_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        // 2) Convert field_agents table if it exists and is NOT already junction-style
        if (Schema::hasTable('field_agents') && !Schema::hasColumn('field_agents', 'user_id')) {
            $now = Carbon::now();

            // Snapshot old rows (best-effort columns)
            $oldRows = DB::table('field_agents')->get();

            // Create new junction table
            Schema::create('field_agents_new', function (Blueprint $table) {
                $table->uuid('user_id')->primary();
                $table->decimal('commission_per_device', 10, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            foreach ($oldRows as $row) {
                $oldId = $row->id ?? null;
                $name = $row->name ?? 'Field Agent';
                $phone = $row->phone ?? null;
                $branchId = $row->branch_id ?? null;
                $commission = isset($row->commission_per_device) ? (float) $row->commission_per_device : 0.0;
                $isActive = isset($row->is_active) ? (bool) $row->is_active : true;

                // Make sure we have a unique email for the new user
                $email = $row->email ?? null;
                if (!$email) {
                    $email = 'fieldagent+' . Str::uuid() . '@example.local';
                }

                // If email already exists, also generate a placeholder
                $emailExists = DB::table('users')->where('email', $email)->exists();
                if ($emailExists) {
                    $email = 'fieldagent+' . Str::uuid() . '@example.local';
                }

                $newUserId = (string) Str::uuid();
                DB::table('users')->insert([
                    'id' => $newUserId,
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(24)),
                    'role' => 'staff',
                    'branch_id' => $branchId,
                    'phone' => $phone,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('field_agents_new')->insert([
                    'user_id' => $newUserId,
                    'commission_per_device' => $commission,
                    'is_active' => $isActive,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Relink historical commissions (old field_agents.id -> users.id)
                if ($oldId) {
                    DB::table('sale_items')
                        ->where('field_agent_id', $oldId)
                        ->update(['field_agent_id' => $newUserId]);
                }
            }

            // Replace old table
            Schema::drop('field_agents');
            Schema::rename('field_agents_new', 'field_agents');
        }

        // If field_agents doesn't exist (fresh-ish weird state), create it
        if (!Schema::hasTable('field_agents')) {
            Schema::create('field_agents', function (Blueprint $table) {
                $table->uuid('user_id')->primary();
                $table->decimal('commission_per_device', 10, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 3) Re-add sale_items FK to users
        try {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->foreign('field_agent_id')->references('id')->on('users')->onDelete('set null');
            });
        } catch (\Throwable $e) {
            // ignore (could already exist on some DBs)
        }
    }

    public function down(): void
    {
        // Best-effort rollback: keep sale_items pointing to users; drop field_agents table
        try {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropForeign(['field_agent_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::dropIfExists('field_agents');

        try {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->foreign('field_agent_id')->references('id')->on('users')->onDelete('set null');
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }
};


