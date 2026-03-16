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

        $branchesData = [
            ['region' => 'ARUSHA', 'location' => 'Vunja bei', 'phone' => '0625 521 506'],
            ['region' => 'MOSHI', 'location' => 'Ofisi za BM', 'phone' => '0794 660 093'],
            ['region' => 'GEITA', 'location' => 'Nyankumbu', 'phone' => '0745 851 663'],
            ['region' => 'MWANZA', 'location' => 'Mirongo Sokoni', 'phone' => '0793 259 567'],
            ['region' => 'DODOMA', 'location' => 'Kidia Hotel', 'phone' => '0769 698 431'],
            ['region' => 'TANGA', 'location' => 'Br ya 15, taifa road', 'phone' => '0793 531 289'],
            ['region' => 'MBEYA', 'location' => 'Hospital ya Uhai', 'phone' => '0752 279 030'],
            ['region' => 'MOROGORO', 'location' => 'Mtaa wa Konga', 'phone' => '0760 892 879'],
            ['region' => 'SINGIDA', 'location' => 'Ipembe road', 'phone' => '0746 677 111'],
            ['region' => 'ZANZIBAR', 'location' => 'Darajani', 'phone' => '0782 395 726'],
            ['region' => 'DAR', 'location' => 'Kkoo, Kanisa la KKKT', 'phone' => '0712 497 788'],
            ['region' => 'TABORA', 'location' => 'Chem chem', 'phone' => '0766 310 177'],
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

        $darBranch = Branch::where('code', 'DAR-001')->first();
        if ($darBranch) {
            Branch::where('code', '!=', 'DAR-001')->update(['head_branch_id' => $darBranch->id]);
        }

        $defaultPassword = 'password';

        // Admin users: use role_id and role from Role model; credentials emailed on first create (via User model)
        $adminUsers = [
            ['name' => 'Blabs Technologies', 'email' => 'blabstechnologies@gmail.com'],
            ['name' => 'Shujaa Michael', 'email' => 'shujaamichael@gmail.com'],
            ['name' => 'Kelvin Njoroge', 'email' => 'njorogemkelvin@gmail.com'],
            ['name' => 'Nasra Athumani', 'email' => 'nasra.athumani@kimaromobile.co.tz'],
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
                    'branch_id' => $darBranch?->id,
                    'phone' => null,
                ]
            );
        }

        $branchCodeByLabel = [
            'Dodoma' => 'DODOMA-001',
            'Geita' => 'GEITA-001',
            'Mwanza' => 'MWANZA-001',
            'Tanga' => 'TANGA-001',
            'Morogoro' => 'MOROGORO-001',
            'Zanzibar' => 'ZANZIBAR-001',
            'Tabora' => 'TABORA-001',
            'Singida' => 'SINGIDA-001',
            'Mbeya' => 'MBEYA-001',
            'Mbagala' => 'DAR-001',
            'Manzese' => 'DAR-001',
            'Kariakoo' => 'DAR-001',
            'Kusini 1' => 'DAR-001',
            '' => 'DAR-001',
        ];

        $staffList = [
            ['name' => 'Warda Ramadhani', 'email' => 'warda.ramadhani@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Dodoma'],
            ['name' => 'Lemi Emily', 'email' => 'lemi.emily@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Geita'],
            ['name' => 'Irene Herbon', 'email' => 'irene.herbon@kimaromobile.com', 'role_slug' => 'staff', 'branch_label' => 'Mwanza'],
            ['name' => 'Subira Mashaka', 'email' => 'subira.mashaka@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Tanga'],
            ['name' => 'Eliah Mlagwa', 'email' => 'eliah.mdogo@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Morogoro'],
            ['name' => 'Elizabeth Kajigiri', 'email' => 'elizabeth.kajigiri@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Zanzibar'],
            ['name' => 'William Biseko', 'email' => 'william.biseko@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Tabora'],
            ['name' => 'Rahma Kingu', 'email' => 'rahma.kingu@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Singida'],
            ['name' => 'Deogratias Myinga', 'email' => 'deogratias.myinga@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Mbeya'],
            ['name' => 'Edna Masunga', 'email' => 'edna.masunga@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Mbagala'],
            ['name' => 'Emmanuel Mijumbo', 'email' => 'emmanuel.mijumbo@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Manzese'],
            ['name' => 'Mwanaheri Masudi', 'email' => 'mwanaheri.masudi@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Kariakoo'],
            ['name' => 'Alfa Paulo', 'email' => 'alfa.paulo@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => 'Kusini 1'],
            ['name' => 'Debora Owisso', 'email' => 'debora.owisso@kimaromobile.co.tz', 'role_slug' => 'head_branch_manager', 'branch_label' => ''],
            ['name' => 'Paul Mgaya', 'email' => 'paul.mgaya@kimaromobile.co.tz', 'role_slug' => 'staff', 'branch_label' => ''],
        ];

        foreach ($staffList as $staff) {
            $branchCode = $branchCodeByLabel[$staff['branch_label']] ?? 'DAR-001';
            $branch = Branch::where('code', $branchCode)->first();
            $role = $staff['role_slug'] === 'head_branch_manager' ? $headBranchManagerRole : $staffRole;
            if (! $branch || ! $role) {
                continue;
            }
            User::$plainPasswordForNewUser = $defaultPassword;
            User::firstOrCreate(
                ['email' => $staff['email']],
                [
                    'name' => $staff['name'],
                    'password' => Hash::make($defaultPassword),
                    'role_id' => $role->id,
                    'role' => $role->slug,
                    'branch_id' => $branch->id,
                    'phone' => null,
                ]
            );
        }

        $this->call(PettyCashCategorySeeder::class);
    }
}
