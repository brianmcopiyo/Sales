<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add stock request permissions. Additive only – no existing data is modified or removed.
     */
    public function up(): void
    {
        $permissionsToAdd = [
            [
                'slug' => 'stock-requests.view',
                'name' => 'View Stock Requests',
                'description' => 'View and respond to stock requests (my requests and incoming). Required to receive stock request notifications.',
                'module' => 'stock-requests',
            ],
            [
                'slug' => 'stock-requests.create',
                'name' => 'Request Stock from Branch',
                'description' => 'Create stock requests from other branches and approve or reject incoming requests.',
                'module' => 'stock-requests',
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

    /**
     * Remove only the permissions added in up(). No other data is touched.
     */
    public function down(): void
    {
        $slugs = ['stock-requests.view', 'stock-requests.create'];
        $perms = DB::table('permissions')->whereIn('slug', $slugs)->get();
        foreach ($perms as $perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
