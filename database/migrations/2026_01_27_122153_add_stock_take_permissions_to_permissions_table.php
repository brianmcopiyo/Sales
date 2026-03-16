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
            // Stock Takes Module
            [
                'id' => Str::uuid()->toString(),
                'name' => 'View Stock Takes',
                'slug' => 'stock-takes.view',
                'description' => 'View stock take sessions',
                'module' => 'stock-takes',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Create Stock Takes',
                'slug' => 'stock-takes.create',
                'description' => 'Create new stock take sessions',
                'module' => 'stock-takes',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Update Stock Takes',
                'slug' => 'stock-takes.update',
                'description' => 'Update stock take items and counts',
                'module' => 'stock-takes',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Approve Stock Takes',
                'slug' => 'stock-takes.approve',
                'description' => 'Approve stock takes and apply adjustments',
                'module' => 'stock-takes',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Cancel Stock Takes',
                'slug' => 'stock-takes.cancel',
                'description' => 'Cancel stock take sessions',
                'module' => 'stock-takes',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Stock Adjustments Module
            [
                'id' => Str::uuid()->toString(),
                'name' => 'View Stock Adjustments',
                'slug' => 'stock-adjustments.view',
                'description' => 'View stock adjustment history',
                'module' => 'stock-adjustments',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Create Stock Adjustments',
                'slug' => 'stock-adjustments.create',
                'description' => 'Create manual stock adjustments',
                'module' => 'stock-adjustments',
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
            'stock-takes.view',
            'stock-takes.create',
            'stock-takes.update',
            'stock-takes.approve',
            'stock-takes.cancel',
            'stock-adjustments.view',
            'stock-adjustments.create',
        ])->delete();
    }
};
