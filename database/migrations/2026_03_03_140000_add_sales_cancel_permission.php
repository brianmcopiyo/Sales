<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add permission for cancelling pending sales (initiator or users with this permission).
     * Safe for production: only adds if slug does not exist; uses insertOrIgnore for role_permission.
     */
    public function up(): void
    {
        $slug = 'sales.cancel';
        if (DB::table('permissions')->where('slug', $slug)->exists()) {
            return;
        }

        $permId = (string) Str::uuid();
        DB::table('permissions')->insert([
            'id' => $permId,
            'name' => 'Cancel pending sales',
            'slug' => $slug,
            'description' => 'Can cancel pending sales (in addition to the sale initiator).',
            'module' => 'sales',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleSlugs = ['admin', 'head_branch_manager', 'regional_branch_manager'];
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
        $perm = DB::table('permissions')->where('slug', 'sales.cancel')->first();
        if ($perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
