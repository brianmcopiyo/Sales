<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add customer-disbursements.approve permission for secondary approval. Additive only.
     */
    public function up(): void
    {
        $slug = 'customer-disbursements.approve';
        if (DB::table('permissions')->where('slug', $slug)->exists()) {
            return;
        }

        $permId = (string) Str::uuid();
        DB::table('permissions')->insert([
            'id' => $permId,
            'name' => 'Approve Customer Disbursements',
            'slug' => $slug,
            'description' => 'Approve or reject customer support disbursements (secondary approval for auditing).',
            'module' => 'customer-disbursements',
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
        $perm = DB::table('permissions')->where('slug', 'customer-disbursements.approve')->first();
        if ($perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
