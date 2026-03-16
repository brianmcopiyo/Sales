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

        // Admin gets all permissions automatically (handled by existing logic)
        // But we'll ensure it here too
        if (isset($roleMap['admin'])) {
            $stockTakePermissions = DB::table('permissions')
                ->whereIn('slug', [
                    'stock-takes.view',
                    'stock-takes.create',
                    'stock-takes.update',
                    'stock-takes.approve',
                    'stock-takes.cancel',
                    'stock-adjustments.view',
                    'stock-adjustments.create',
                ])
                ->where('is_active', true)
                ->pluck('id');
            
            foreach ($stockTakePermissions as $permissionId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleMap['admin'],
                    'permission_id' => $permissionId,
                ]);
            }
        }

        // Head Branch Manager - All stock take permissions
        if (isset($roleMap['head_branch_manager'])) {
            $headBranchManagerPermissions = [
                'stock-takes.view',
                'stock-takes.create',
                'stock-takes.update',
                'stock-takes.approve',
                'stock-takes.cancel',
                'stock-adjustments.view',
                'stock-adjustments.create',
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

        // Regional Branch Manager - View, create, update, approve (no cancel)
        if (isset($roleMap['regional_branch_manager'])) {
            $regionalBranchManagerPermissions = [
                'stock-takes.view',
                'stock-takes.create',
                'stock-takes.update',
                'stock-takes.approve',
                'stock-adjustments.view',
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

        // Staff - View, create, update (no approve or cancel)
        if (isset($roleMap['staff'])) {
            $staffPermissions = [
                'stock-takes.view',
                'stock-takes.create',
                'stock-takes.update',
                'stock-adjustments.view',
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
                'stock-takes.view',
                'stock-takes.create',
                'stock-takes.update',
                'stock-takes.approve',
                'stock-takes.cancel',
                'stock-adjustments.view',
                'stock-adjustments.create',
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
