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
                'name' => 'View Outlets',
                'slug' => 'outlets.view',
                'description' => 'View outlet list and details',
                'module' => 'distribution',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Manage Outlets',
                'slug' => 'outlets.manage',
                'description' => 'Create, update, delete outlets',
                'module' => 'distribution',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Create Check-ins',
                'slug' => 'checkins.create',
                'description' => 'Check in at outlets',
                'module' => 'distribution',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'View Check-ins',
                'slug' => 'checkins.view',
                'description' => 'View check-in history and DCR',
                'module' => 'distribution',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Distribution Reports',
                'slug' => 'distribution.reports',
                'description' => 'View daily call reports and distribution reports',
                'module' => 'distribution',
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
        DB::table('permissions')->whereIn('slug', [
            'outlets.view',
            'outlets.manage',
            'checkins.create',
            'checkins.view',
            'distribution.reports',
        ])->delete();
    }
};
