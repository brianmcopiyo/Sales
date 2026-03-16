<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get role IDs
        $roleMap = [];
        $roles = ['admin', 'head_branch_manager', 'regional_branch_manager', 'staff'];
        foreach ($roles as $roleSlug) {
            $role = DB::table('roles')->where('slug', $roleSlug)->first();
            if ($role) {
                $roleMap[$roleSlug] = $role->id;
            }
        }

        // Admin gets all permissions
        if (isset($roleMap['admin'])) {
            $inventoryPermissions = DB::table('permissions')
                ->whereIn('slug', [
                    'inventory.view',
                    'inventory.movements.view',
                    'inventory.alerts.view',
                    'inventory.alerts.manage',
                ])
                ->where('is_active', true)
                ->pluck('id');
            
            foreach ($inventoryPermissions as $permissionId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleMap['admin'],
                    'permission_id' => $permissionId,
                ]);
            }
        }

        // Head Branch Manager - All inventory permissions
        if (isset($roleMap['head_branch_manager'])) {
            $headBranchManagerPermissions = [
                'inventory.view',
                'inventory.movements.view',
                'inventory.alerts.view',
                'inventory.alerts.manage',
            ];
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', $headBranchManagerPermissions)
                ->where('is_active', true)
                ->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleMap['head_branch_manager'],
                    'permission_id' => $permissionId,
                ]);
            }
        }

        // Regional Branch Manager - View permissions
        if (isset($roleMap['regional_branch_manager'])) {
            $regionalBranchManagerPermissions = [
                'inventory.view',
                'inventory.movements.view',
                'inventory.alerts.view',
                'inventory.alerts.manage',
            ];
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', $regionalBranchManagerPermissions)
                ->where('is_active', true)
                ->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleMap['regional_branch_manager'],
                    'permission_id' => $permissionId,
                ]);
            }
        }

        // Staff - View only
        if (isset($roleMap['staff'])) {
            $staffPermissions = [
                'inventory.view',
                'inventory.movements.view',
                'inventory.alerts.view',
            ];
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', $staffPermissions)
                ->where('is_active', true)
                ->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleMap['staff'],
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get permission IDs
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', [
                'inventory.view',
                'inventory.movements.view',
                'inventory.alerts.view',
                'inventory.alerts.manage',
            ])
            ->pluck('id');

        // Remove from all roles
        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permission')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }
};
