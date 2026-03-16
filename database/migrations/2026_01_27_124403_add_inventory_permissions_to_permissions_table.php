<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            // Inventory Module
            [
                'id' => Str::uuid()->toString(),
                'name' => 'View Inventory',
                'slug' => 'inventory.view',
                'description' => 'View inventory dashboard',
                'module' => 'inventory',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'View Inventory Movements',
                'slug' => 'inventory.movements.view',
                'description' => 'View inventory movement history',
                'module' => 'inventory',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'View Inventory Alerts',
                'slug' => 'inventory.alerts.view',
                'description' => 'View inventory alerts',
                'module' => 'inventory',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Manage Inventory Alerts',
                'slug' => 'inventory.alerts.manage',
                'description' => 'Resolve and manage inventory alerts',
                'module' => 'inventory',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('permissions')->insert($permissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', [
            'inventory.view',
            'inventory.movements.view',
            'inventory.alerts.view',
            'inventory.alerts.manage',
        ])->delete();
    }
};
