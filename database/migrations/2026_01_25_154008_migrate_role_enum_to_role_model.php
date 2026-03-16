<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration safely migrates from role enum to role_id foreign key.
     * It creates roles, assigns permissions, and migrates existing user data.
     */
    public function up(): void
    {
        // Check if is_protected column exists
        $hasProtectedColumn = \Illuminate\Support\Facades\Schema::hasColumn('roles', 'is_protected');

        // Step 1: Create roles for each enum value
        $roles = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'System administrator with full access',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Head Branch Manager',
                'slug' => 'head_branch_manager',
                'description' => 'Head branch manager with regional oversight',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Regional Branch Manager',
                'slug' => 'regional_branch_manager',
                'description' => 'Regional branch manager',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Staff',
                'slug' => 'staff',
                'description' => 'Staff member',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Customer',
                'slug' => 'customer',
                'description' => 'Customer account',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert roles (only if they don't exist)
        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore($role);
        }

        // If is_protected column exists, mark admin as protected
        if ($hasProtectedColumn) {
            DB::table('roles')
                ->where('slug', 'admin')
                ->update(['is_protected' => true]);
        }

        // Step 2: Get role IDs for permission assignment
        $roleMap = [];
        foreach ($roles as $role) {
            $roleRecord = DB::table('roles')->where('slug', $role['slug'])->first();
            if ($roleRecord) {
                $roleMap[$role['slug']] = $roleRecord->id;
            }
        }

        // Step 3: Assign permissions to each role
        // Admin gets all permissions
        if (isset($roleMap['admin'])) {
            $allPermissions = DB::table('permissions')->where('is_active', true)->pluck('id');
            foreach ($allPermissions as $permissionId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleMap['admin'],
                    'permission_id' => $permissionId,
                ]);
            }
        }

        // Head Branch Manager permissions
        if (isset($roleMap['head_branch_manager'])) {
            $headBranchManagerPermissions = [
                'dashboard.view',
                'products.view', 'products.create', 'products.update',
                'brands.view', 'brands.create', 'brands.update',
                'regions.view',
                'branches.view', 'branches.update', 'branches.manage-users',
                'stock-transfers.view', 'stock-transfers.create', 'stock-transfers.receive', 'stock-transfers.cancel',
                'stock-management.view', 'stock-management.approve', 'stock-management.restock',
                'branch-stocks.view', 'branch-stocks.create', 'branch-stocks.update',
                'sales.view', 'sales.create',
                'transactions.view',
                'customers.view', 'customers.create', 'customers.update',
                'customer-disbursements.view', 'customer-disbursements.create',
                'devices.view', 'devices.create', 'devices.update',
                'tickets.view', 'tickets.create', 'tickets.update', 'tickets.reply', 'tickets.disbursements',
                'users.view', 'users.create', 'users.update',
                'field-agents.view', 'field-agents.create', 'field-agents.update',
                'commission-disbursements.view', 'commission-disbursements.approve',
                'activity-logs.view',
                'profile.view', 'profile.update',
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

        // Regional Branch Manager permissions
        if (isset($roleMap['regional_branch_manager'])) {
            $regionalBranchManagerPermissions = [
                'dashboard.view',
                'products.view',
                'brands.view',
                'regions.view',
                'branches.view',
                'stock-transfers.view', 'stock-transfers.create', 'stock-transfers.receive',
                'stock-management.view', 'stock-management.approve',
                'branch-stocks.view', 'branch-stocks.create', 'branch-stocks.update',
                'sales.view', 'sales.create',
                'transactions.view',
                'customers.view', 'customers.create', 'customers.update',
                'customer-disbursements.view', 'customer-disbursements.create',
                'devices.view', 'devices.create', 'devices.update',
                'tickets.view', 'tickets.create', 'tickets.update', 'tickets.reply',
                'users.view',
                'field-agents.view',
                'commission-disbursements.view', 'commission-disbursements.approve',
                'activity-logs.view',
                'profile.view', 'profile.update',
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

        // Staff permissions
        if (isset($roleMap['staff'])) {
            $staffPermissions = [
                'dashboard.view',
                'products.view',
                'brands.view',
                'regions.view',
                'branches.view',
                'stock-transfers.view', 'stock-transfers.create', 'stock-transfers.receive',
                'branch-stocks.view',
                'sales.view', 'sales.create',
                'transactions.view',
                'customers.view', 'customers.create', 'customers.update',
                'customer-disbursements.view', 'customer-disbursements.create',
                'devices.view', 'devices.create', 'devices.update',
                'tickets.view', 'tickets.create', 'tickets.update', 'tickets.reply',
                'field-agents.view',
                'commission-disbursements.view',
                'profile.view', 'profile.update',
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

        // Customer permissions
        if (isset($roleMap['customer'])) {
            $customerPermissions = [
                'dashboard.view',
                'sales.view',
                'transactions.view',
                'tickets.view', 'tickets.create', 'tickets.update', 'tickets.reply',
                'profile.view', 'profile.update',
            ];
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', $customerPermissions)
                ->where('is_active', true)
                ->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleMap['customer'],
                    'permission_id' => $permissionId,
                ]);
            }
        }

        // Step 4: Migrate existing users' role enum to role_id
        // Map enum values to role slugs
        $roleMapping = [
            'admin' => 'admin',
            'head_branch_manager' => 'head_branch_manager',
            'regional_branch_manager' => 'regional_branch_manager',
            'staff' => 'staff',
            'customer' => 'customer',
        ];

        // Update users with role_id based on their role enum
        foreach ($roleMapping as $enumValue => $slug) {
            $roleId = $roleMap[$slug] ?? null;
            if ($roleId) {
                DB::table('users')
                    ->where('role', $enumValue)
                    ->whereNull('role_id') // Only update if role_id is null (safe migration)
                    ->update(['role_id' => $roleId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     * Note: We don't remove role_id or roles as this could cause data loss.
     * The role enum column remains for backward compatibility.
     */
    public function down(): void
    {
        // Set role_id to null for all users (reversible)
        DB::table('users')->update(['role_id' => null]);
        
        // Optionally remove role_permission assignments (but keep roles)
        // DB::table('role_permission')->truncate();
    }
};
