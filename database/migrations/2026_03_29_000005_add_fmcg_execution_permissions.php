<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            ['name' => 'Manage Planned Visits', 'slug' => 'planned-visits.manage', 'description' => 'Create and manage planned field visits', 'module' => 'distribution'],
            ['name' => 'View DCR', 'slug' => 'dcr.view', 'description' => 'View daily call reports', 'module' => 'distribution'],
            ['name' => 'Manage Audits', 'slug' => 'audits.manage', 'description' => 'Create templates and submit outlet audits', 'module' => 'distribution'],
            ['name' => 'View Audit Reports', 'slug' => 'audits.reports', 'description' => 'View audit analytics and compliance reports', 'module' => 'distribution'],
            ['name' => 'Manage Attendance', 'slug' => 'attendance.manage', 'description' => 'Clock in/out and manage attendance', 'module' => 'distribution'],
            ['name' => 'View Attendance', 'slug' => 'attendance.view', 'description' => 'View attendance logs', 'module' => 'distribution'],
            ['name' => 'Create Field Expenses', 'slug' => 'expenses.create', 'description' => 'Submit field expense claims', 'module' => 'distribution'],
            ['name' => 'Approve Field Expenses', 'slug' => 'expenses.approve', 'description' => 'Review and approve field expense claims', 'module' => 'distribution'],
            ['name' => 'View Field Expenses', 'slug' => 'expenses.view', 'description' => 'View field expenses and reimbursements', 'module' => 'distribution'],
        ];

        $insertedIds = [];
        foreach ($permissions as $permission) {
            $existingId = DB::table('permissions')->where('slug', $permission['slug'])->value('id');
            if ($existingId) {
                $insertedIds[] = $existingId;
                continue;
            }
            $id = (string) Str::uuid();
            DB::table('permissions')->insert([
                'id' => $id,
                'name' => $permission['name'],
                'slug' => $permission['slug'],
                'description' => $permission['description'],
                'module' => $permission['module'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $insertedIds[] = $id;
        }

        $adminRoles = DB::table('roles')
            ->whereIn('slug', ['admin', 'super_admin'])
            ->where('is_active', true)
            ->pluck('id');

        foreach ($adminRoles as $roleId) {
            foreach ($insertedIds as $permissionId) {
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
            'planned-visits.manage',
            'dcr.view',
            'audits.manage',
            'audits.reports',
            'attendance.manage',
            'attendance.view',
            'expenses.create',
            'expenses.approve',
            'expenses.view',
        ])->delete();
    }
};
