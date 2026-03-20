<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'View Schemes',
                'slug' => 'schemes.view',
                'description' => 'View scheme and promotion list',
                'module' => 'schemes',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Manage Schemes',
                'slug' => 'schemes.manage',
                'description' => 'Create, update, delete schemes and promotions',
                'module' => 'schemes',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('permissions')->insert($permissions);

        $ids = collect($permissions)->pluck('id')->toArray();
        $adminRoles = DB::table('roles')->whereIn('slug', ['admin', 'super_admin'])->where('is_active', true)->pluck('id');
        $now = now();
        foreach ($adminRoles as $roleId) {
            foreach ($ids as $permissionId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', ['schemes.view', 'schemes.manage'])->delete();
    }
};
