<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Region;
use App\Models\Branch;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure all branches have a region_id before making it required
        $branchesWithoutRegion = Branch::whereNull('region_id')->count();
        
        if ($branchesWithoutRegion > 0) {
            // Get or create a default region
            $defaultRegion = Region::firstOrCreate(
                ['name' => 'Default Region'],
                [
                    'description' => 'Default region for branches without a specific region assignment',
                    'is_active' => true,
                ]
            );

            // Assign all branches without a region to the default region
            Branch::whereNull('region_id')->update(['region_id' => $defaultRegion->id]);
        }

        Schema::table('branches', function (Blueprint $table) {
            // Add foreign key constraint for region_id
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
        });
    }
};

