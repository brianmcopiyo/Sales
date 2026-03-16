<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure admin (and super_admin) roles have all active permissions.
     * Run the migrations.
     */
    public function up(): void
    {
        $adminRoleSlugs = ['admin', 'super_admin'];

        $adminRoles = DB::table('roles')
            ->whereIn('slug', $adminRoleSlugs)
            ->where('is_active', true)
            ->get();

        if ($adminRoles->isEmpty()) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('is_active', true)
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($adminRoles as $role) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     * We do not remove admin permissions on rollback; other migrations assign specific permissions.
     */
    public function down(): void
    {
        // No-op: leave admin role permissions as-is to avoid breaking access
    }
};
