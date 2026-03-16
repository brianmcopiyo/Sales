<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $id = Str::uuid()->toString();
        DB::table('permissions')->insert([
            'id' => $id,
            'name' => 'Reject Stock Transfers',
            'slug' => 'stock-transfers.reject',
            'description' => 'Reject stock transfers with a reason (branch admins only)',
            'module' => 'stock-transfers',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleSlugs = ['admin', 'head_branch_manager', 'regional_branch_manager'];
        $roles = DB::table('roles')->whereIn('slug', $roleSlugs)->get()->keyBy('slug');
        foreach ($roleSlugs as $slug) {
            $role = $roles->get($slug);
            if ($role) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $role->id,
                    'permission_id' => $id,
                ]);
            }
        }
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->where('slug', 'stock-transfers.reject')->first();
        if ($perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
