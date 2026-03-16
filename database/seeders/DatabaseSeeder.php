<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;
use App\Models\Region;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles are created by migration 2026_01_25_154008; ensure we resolve by slug for role_id + role
        $adminRole = Role::where('slug', 'admin')->first();
        $staffRole = Role::where('slug', 'staff')->first();
        $headBranchManagerRole = Role::where('slug', 'head_branch_manager')->first();
        if (! $adminRole || ! $staffRole || ! $headBranchManagerRole) {
            $this->command->warn('Roles not found. Run migrations first (roles are created by migrate_role_enum_to_role_model).');
            return;
        }

        // Minimal placeholder: one region and one branch for the admin user
        $branchesData = [
            ['region' => 'Head Office', 'location' => 'Head Office', 'phone' => null],
        ];

        $regions = [];
        foreach ($branchesData as $item) {
            $name = $item['region'];
            if (! isset($regions[$name])) {
                $regions[$name] = Region::firstOrCreate(
                    ['name' => $name],
                    [
                        'description' => $name . ' region',
                        'is_active' => true,
                    ]
                );
            }
        }

        foreach ($branchesData as $item) {
            $region = $regions[$item['region']];
            $code = $item['region'] . '-001';
            Branch::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $item['region'] . ' Branch',
                    'region_id' => $region->id,
                    'address' => $item['location'],
                    'phone' => $item['phone'],
                    'email' => null,
                    'head_branch_id' => null,
                    'is_active' => true,
                ]
            );
        }

        $headBranch = Branch::first();
        if ($headBranch) {
            Branch::where('id', '!=', $headBranch->id)->update(['head_branch_id' => $headBranch->id]);
        }

        $defaultPassword = 'password';

        // Admin user only (no third-party personal data)
        $adminUsers = [
            ['name' => 'Blabs Technologies', 'email' => 'blabstechnologies@gmail.com'],
        ];
        foreach ($adminUsers as $admin) {
            User::$plainPasswordForNewUser = $defaultPassword;
            User::firstOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($defaultPassword),
                    'role_id' => $adminRole->id,
                    'role' => $adminRole->slug,
                    'branch_id' => $headBranch?->id,
                    'phone' => null,
                ]
            );
        }

        $this->call(PettyCashCategorySeeder::class);
    }
}
