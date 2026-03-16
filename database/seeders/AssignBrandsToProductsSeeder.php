<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class AssignBrandsToProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a default brand for products without brands
        $defaultBrand = Brand::firstOrCreate(
            ['name' => 'Unbranded'],
            [
                'description' => 'Default brand for products without a specific brand assignment',
                'is_active' => true,
            ]
        );

        // Assign all products without a brand to the default brand
        $productsWithoutBrand = Product::whereNull('brand_id')->get();
        
        if ($productsWithoutBrand->count() > 0) {
            Product::whereNull('brand_id')->update(['brand_id' => $defaultBrand->id]);
            
            $this->command->info("Assigned {$productsWithoutBrand->count()} product(s) to the default brand '{$defaultBrand->name}'.");
        } else {
            $this->command->info('All products already have brands assigned.');
        }

        // If there are products with the old 'brand' string field, try to match them to existing brands
        // Note: This assumes the old 'brand' column still exists in the database
        // If it doesn't, this part will be skipped
        try {
            $productsWithOldBrand = DB::table('products')
                ->whereNotNull('brand')
                ->whereNull('brand_id')
                ->get();

            foreach ($productsWithOldBrand as $product) {
                if (!empty($product->brand)) {
                    // Try to find or create a brand with the old brand name
                    $brand = Brand::firstOrCreate(
                        ['name' => $product->brand],
                        [
                            'description' => "Brand created from existing product data",
                            'is_active' => true,
                        ]
                    );

                    // Update the product with the brand_id
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['brand_id' => $brand->id]);

                    $this->command->info("Assigned product '{$product->name}' to brand '{$brand->name}'.");
                }
            }
        } catch (\Exception $e) {
            // If the old 'brand' column doesn't exist, that's fine - migration already removed it
            $this->command->warn('Could not process old brand field (this is normal if migration already ran).');
        }
    }
}
