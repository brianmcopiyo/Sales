<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove profile permissions from role_permission pivot table first
        DB::table('role_permission')
            ->whereIn('permission_id', function($query) {
                $query->select('id')
                    ->from('permissions')
                    ->where('module', 'profile');
            })
            ->delete();

        // Delete profile permissions
        DB::table('permissions')
            ->where('module', 'profile')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-insert profile permissions if needed
        $profilePermissions = [
            [
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'View Own Profile',
                'slug' => 'profile.view',
                'description' => 'View own profile',
                'module' => 'profile',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'Update Own Profile',
                'slug' => 'profile.update',
                'description' => 'Update own profile information',
                'module' => 'profile',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('permissions')->insert($profilePermissions);
    }
};
