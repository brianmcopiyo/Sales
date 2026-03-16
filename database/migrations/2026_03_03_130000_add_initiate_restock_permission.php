<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add permission for users who can initiate (create) restock orders.
     * Safe for production: only adds if slug does not exist; uses insertOrIgnore for role_permission.
     */
    public function up(): void
    {
        $slug = 'stock-management.initiate-restock';
        if (DB::table('permissions')->where('slug', $slug)->exists()) {
            return;
        }

        $permId = (string) Str::uuid();
        DB::table('permissions')->insert([
            'id' => $permId,
            'name' => 'Initiate restock order',
            'slug' => $slug,
            'description' => 'Can create and submit restock orders. Does not include receiving, approving, or rejecting orders.',
            'module' => 'stock-management',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleSlugs = ['admin', 'head_branch_manager', 'regional_branch_manager', 'staff'];
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

    public function down(): void
    {
        $perm = DB::table('permissions')->where('slug', 'stock-management.initiate-restock')->first();
        if ($perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
