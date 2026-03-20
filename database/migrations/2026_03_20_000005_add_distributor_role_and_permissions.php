<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Create distributor role
        $roleId = Str::uuid()->toString();
        DB::table('roles')->insertOrIgnore([
            'id'         => $roleId,
            'name'       => 'Distributor',
            'slug'       => 'distributor',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Re-fetch in case it already existed
        $roleId = DB::table('roles')->where('slug', 'distributor')->value('id');

        // Permissions for the portal
        $permissions = [
            [
                'id'          => Str::uuid()->toString(),
                'name'        => 'Access Distributor Portal',
                'slug'        => 'portal.access',
                'description' => 'Base access to the distributor self-service portal',
                'module'      => 'portal',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => Str::uuid()->toString(),
                'name'        => 'Submit Portal Claims',
                'slug'        => 'portal.claims.create',
                'description' => 'Submit claims from the distributor portal',
                'module'      => 'portal',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => Str::uuid()->toString(),
                'name'        => 'View Portal Claims',
                'slug'        => 'portal.claims.view',
                'description' => 'View own claims in the distributor portal',
                'module'      => 'portal',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => Str::uuid()->toString(),
                'name'        => 'Export Portal Reports',
                'slug'        => 'portal.reports.export',
                'description' => 'Download Excel exports from the distributor portal',
                'module'      => 'portal',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => Str::uuid()->toString(),
                'name'        => 'Manage Distributor Portal',
                'slug'        => 'distributor-portal.manage',
                'description' => 'Admin: view and manage distributor portal profiles',
                'module'      => 'distributor-portal',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => Str::uuid()->toString(),
                'name'        => 'Approve Distributor Claims',
                'slug'        => 'distributor-portal.claims.approve',
                'description' => 'Admin: approve or reject distributor claims',
                'module'      => 'distributor-portal',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => Str::uuid()->toString(),
                'name'        => 'Manage Distributor Targets',
                'slug'        => 'distributor-portal.targets.manage',
                'description' => 'Admin: set and delete targets for distributors',
                'module'      => 'distributor-portal',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        DB::table('permissions')->insert($permissions);

        // Assign portal permissions to the distributor role
        $portalPermissionSlugs = ['portal.access', 'portal.claims.create', 'portal.claims.view', 'portal.reports.export'];
        $portalPermissionIds = DB::table('permissions')->whereIn('slug', $portalPermissionSlugs)->pluck('id');
        $now = now();
        foreach ($portalPermissionIds as $permissionId) {
            DB::table('role_permission')->insertOrIgnore([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        // Assign admin permissions to admin/super_admin roles
        $adminPermissionSlugs = ['distributor-portal.manage', 'distributor-portal.claims.approve', 'distributor-portal.targets.manage'];
        $adminPermissionIds = DB::table('permissions')->whereIn('slug', $adminPermissionSlugs)->pluck('id');
        $adminRoles = DB::table('roles')->whereIn('slug', ['admin', 'super_admin'])->where('is_active', true)->pluck('id');
        foreach ($adminRoles as $adminRoleId) {
            foreach ($adminPermissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id'       => $adminRoleId,
                    'permission_id' => $permissionId,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $slugs = [
            'portal.access', 'portal.claims.create', 'portal.claims.view', 'portal.reports.export',
            'distributor-portal.manage', 'distributor-portal.claims.approve', 'distributor-portal.targets.manage',
        ];
        DB::table('permissions')->whereIn('slug', $slugs)->delete();
        DB::table('roles')->where('slug', 'distributor')->delete();
    }
};
