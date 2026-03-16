<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $defaults = [
            ['name' => 'Rent', 'slug' => 'rent'],
            ['name' => 'Utilities', 'slug' => 'utilities'],
            ['name' => 'Subscriptions', 'slug' => 'subscriptions'],
            ['name' => 'Supplies', 'slug' => 'supplies'],
            ['name' => 'Services', 'slug' => 'services'],
            ['name' => 'Other', 'slug' => 'other'],
        ];

        $now = now();
        foreach ($defaults as $row) {
            DB::table('bill_categories')->insert([
                'id' => (string) Str::uuid(),
                'name' => $row['name'],
                'slug' => $row['slug'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_categories');
    }
};
