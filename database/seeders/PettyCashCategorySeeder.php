<?php

namespace Database\Seeders;

use App\Models\PettyCashCategory;
use Illuminate\Database\Seeder;

class PettyCashCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Office supplies', 'slug' => 'office_supplies', 'sort_order' => 1],
            ['name' => 'Travel', 'slug' => 'travel', 'sort_order' => 2],
            ['name' => 'Postage', 'slug' => 'postage', 'sort_order' => 3],
            ['name' => 'Maintenance', 'slug' => 'maintenance', 'sort_order' => 4],
            ['name' => 'Other', 'slug' => 'other', 'sort_order' => 5],
        ];

        foreach ($categories as $item) {
            PettyCashCategory::firstOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
