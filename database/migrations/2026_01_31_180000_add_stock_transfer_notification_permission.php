<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds permission "Receives stock transfer notification emails" and assigns it to
     * admin, head_branch_manager, regional_branch_manager so one branch contact per branch
     * receives transfer activity emails.
     */
    public function up(): void
    {
        $slug = 'stock-transfers.receive-notifications';
        $permission = DB::table('permissions')->where('slug', $slug)->first();

        if (!$permission) {
            $id = Str::uuid()->toString();
            DB::table('permissions')->insert([
                'id' => $id,
                'name' => 'Receive Stock Transfer Notifications',
                'slug' => $slug,
                'description' => 'Receives stock transfer activity emails for the branch (one recipient per branch).',
                'module' => 'stock-transfers',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $id = $permission->id;
        }

        $roleSlugs = ['admin', 'head_branch_manager', 'regional_branch_manager'];
        $roles = DB::table('roles')->whereIn('slug', $roleSlugs)->get();
        foreach ($roles as $role) {
            DB::table('role_permission')->insertOrIgnore([
                'role_id' => $role->id,
                'permission_id' => $id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = DB::table('permissions')->where('slug', 'stock-transfers.receive-notifications')->first();
        if ($perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
