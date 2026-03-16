<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add agent stock request permissions (separate from branch-to-branch stock-requests).
     * Branch staff need these explicitly; field agents get access automatically by being field agents.
     */
    public function up(): void
    {
        $permissionsToAdd = [
            [
                'slug' => 'agent-stock-requests.view',
                'name' => 'View Agent Stock Requests',
                'description' => 'View agent stock requests (incoming requests from field agents at your branch). Field agents get this automatically.',
                'module' => 'agent-stock-requests',
            ],
            [
                'slug' => 'agent-stock-requests.create',
                'name' => 'Manage Agent Stock Requests',
                'description' => 'Approve, partially approve, reject, or close agent stock requests at your branch. Field agents can submit requests without this permission.',
                'module' => 'agent-stock-requests',
            ],
        ];

        $roleSlugs = ['admin', 'head_branch_manager', 'regional_branch_manager', 'staff'];
        $roleIds = DB::table('roles')->whereIn('slug', $roleSlugs)->pluck('id');

        foreach ($permissionsToAdd as $row) {
            $existing = DB::table('permissions')->where('slug', $row['slug'])->first();
            if (!$existing) {
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
    }

    public function down(): void
    {
        $slugs = ['agent-stock-requests.view', 'agent-stock-requests.create'];
        $perms = DB::table('permissions')->whereIn('slug', $slugs)->get();
        foreach ($perms as $perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
