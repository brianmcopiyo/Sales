<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;
use App\Models\Branch;

class AssignRegionsToBranchesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a default region for branches without regions
        $defaultRegion = Region::firstOrCreate(
            ['name' => 'Default Region'],
            [
                'description' => 'Default region for branches without a specific region assignment',
                'is_active' => true,
            ]
        );

        // Assign all branches without a region to the default region
        $branchesWithoutRegion = Branch::whereNull('region_id')->get();
        
        if ($branchesWithoutRegion->count() > 0) {
            Branch::whereNull('region_id')->update(['region_id' => $defaultRegion->id]);
            
            $this->command->info("Assigned {$branchesWithoutRegion->count()} branch(es) to the default region '{$defaultRegion->name}'.");
        } else {
            $this->command->info('All branches already have regions assigned.');
        }
    }
}
