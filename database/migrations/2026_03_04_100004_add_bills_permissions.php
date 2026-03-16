<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add bills (accounts payable) permissions and assign to roles.
     * view, create, export: admin, head_branch_manager, regional_branch_manager, staff.
     * approve, pay, manage-vendors: admin, head_branch_manager, regional_branch_manager.
     */
    public function up(): void
    {
        $permissionsToAdd = [
            [
                'slug' => 'bills.view',
                'name' => 'View bills',
                'description' => 'View bills list, filters, and bill detail for branches they can access.',
                'module' => 'bills',
                'role_slugs' => ['admin', 'head_branch_manager', 'regional_branch_manager', 'staff'],
            ],
            [
                'slug' => 'bills.create',
                'name' => 'Create bills',
                'description' => 'Create and edit draft bills (record incoming invoices).',
                'module' => 'bills',
                'role_slugs' => ['admin', 'head_branch_manager', 'regional_branch_manager', 'staff'],
            ],
            [
                'slug' => 'bills.approve',
                'name' => 'Approve bills',
                'description' => 'Approve or reject bills.',
                'module' => 'bills',
                'role_slugs' => ['admin', 'head_branch_manager', 'regional_branch_manager'],
            ],
            [
                'slug' => 'bills.pay',
                'name' => 'Mark bills paid',
                'description' => 'Mark bills as paid and record payment date/reference.',
                'module' => 'bills',
                'role_slugs' => ['admin', 'head_branch_manager', 'regional_branch_manager'],
            ],
            [
                'slug' => 'bills.manage-vendors',
                'name' => 'Manage vendors',
                'description' => 'Create and edit vendors.',
                'module' => 'bills',
                'role_slugs' => ['admin', 'head_branch_manager', 'regional_branch_manager'],
            ],
            [
                'slug' => 'bills.export',
                'name' => 'Export bills',
                'description' => 'Export bills with filters.',
                'module' => 'bills',
                'role_slugs' => ['admin', 'head_branch_manager', 'regional_branch_manager', 'staff'],
            ],
        ];

        foreach ($permissionsToAdd as $row) {
            $roleSlugs = $row['role_slugs'];
            unset($row['role_slugs']);

            if (DB::table('permissions')->where('slug', $row['slug'])->exists()) {
                continue;
            }

            $permId = (string) Str::uuid();
            DB::table('permissions')->insert([
                'id' => $permId,
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'module' => $row['module'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $roleIds = DB::table('roles')->whereIn('slug', $roleSlugs)->pluck('id');
            foreach ($roleIds as $roleId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $slugs = [
            'bills.view',
            'bills.create',
            'bills.approve',
            'bills.pay',
            'bills.manage-vendors',
            'bills.export',
        ];
        $perms = DB::table('permissions')->whereIn('slug', $slugs)->get();
        foreach ($perms as $perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
