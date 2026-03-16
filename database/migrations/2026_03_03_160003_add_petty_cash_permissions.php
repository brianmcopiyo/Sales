<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['slug' => 'petty-cash.view', 'name' => 'View petty cash', 'description' => 'View petty cash funds, requests, and history for allowed branches.', 'module' => 'petty-cash'],
            ['slug' => 'petty-cash.request', 'name' => 'Request petty cash', 'description' => 'Create petty cash requests. Only users with this permission can submit a request.', 'module' => 'petty-cash'],
            ['slug' => 'petty-cash.approve', 'name' => 'Approve petty cash', 'description' => 'Approve or reject petty cash requests.', 'module' => 'petty-cash'],
            ['slug' => 'petty-cash.custodian', 'name' => 'Petty cash custodian', 'description' => 'Mark requests as disbursed and manage fund for assigned branch.', 'module' => 'petty-cash'],
            ['slug' => 'petty-cash.replenish', 'name' => 'Replenish petty cash', 'description' => 'Record petty cash fund replenishments.', 'module' => 'petty-cash'],
            ['slug' => 'petty-cash.manage-funds', 'name' => 'Manage petty cash funds', 'description' => 'Create or edit fund settings (limit, custodian).', 'module' => 'petty-cash'],
        ];

        $roleSlugs = ['admin', 'head_branch_manager', 'regional_branch_manager'];
        $roleIds = DB::table('roles')->whereIn('slug', $roleSlugs)->pluck('id');

        foreach ($permissions as $p) {
            if (DB::table('permissions')->where('slug', $p['slug'])->exists()) {
                continue;
            }
            $permId = (string) Str::uuid();
            DB::table('permissions')->insert([
                'id' => $permId,
                'name' => $p['name'],
                'slug' => $p['slug'],
                'description' => $p['description'],
                'module' => $p['module'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($roleIds as $roleId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Give staff view + request only
        $staffRoleId = DB::table('roles')->where('slug', 'staff')->value('id');
        if ($staffRoleId) {
            $viewId = DB::table('permissions')->where('slug', 'petty-cash.view')->value('id');
            $requestId = DB::table('permissions')->where('slug', 'petty-cash.request')->value('id');
            if ($viewId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $staffRoleId,
                    'permission_id' => $viewId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if ($requestId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $staffRoleId,
                    'permission_id' => $requestId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $slugs = [
            'petty-cash.view',
            'petty-cash.request',
            'petty-cash.approve',
            'petty-cash.custodian',
            'petty-cash.replenish',
            'petty-cash.manage-funds',
        ];
        foreach ($slugs as $slug) {
            $perm = DB::table('permissions')->where('slug', $slug)->first();
            if ($perm) {
                DB::table('role_permission')->where('permission_id', $perm->id)->delete();
                DB::table('permissions')->where('id', $perm->id)->delete();
            }
        }
    }
};
