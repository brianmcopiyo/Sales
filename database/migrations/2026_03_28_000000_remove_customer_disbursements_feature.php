<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the customer disbursements feature for production.
     * Safe: drops FKs first, then table/columns; removes permissions last.
     */
    public function up(): void
    {
        // 1. Drop FK and column on tickets that reference customer_disbursements
        if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'disbursement_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropForeign(['disbursement_id']);
            });
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('disbursement_id');
            });
        }

        // 2. Drop the customer_disbursements table
        Schema::dropIfExists('customer_disbursements');

        // 3. Drop total_disbursed from customers (denormalized cache from disbursements)
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'total_disbursed')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('total_disbursed');
            });
        }

        // 4. Remove permissions and role assignments (avoid FK/orphan issues)
        $slugs = [
            'customer-disbursements.view',
            'customer-disbursements.create',
            'customer-disbursements.approve',
            'tickets.disbursements',
        ];
        $ids = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }

    /**
     * Reverse is not fully restorable (data lost). Only re-create table structure and permissions for rollback testing.
     */
    public function down(): void
    {
        // Re-create permissions (optional rollback)
        $slugs = [
            'customer-disbursements.view' => ['name' => 'View Customer Disbursements', 'description' => 'View customer disbursements'],
            'customer-disbursements.create' => ['name' => 'Create Customer Disbursements', 'description' => 'Create customer disbursements'],
            'customer-disbursements.approve' => ['name' => 'Approve Customer Disbursements', 'description' => 'Approve or reject customer disbursements'],
            'tickets.disbursements' => ['name' => 'Create Disbursements From Tickets', 'description' => 'Create disbursements from tickets'],
        ];
        foreach ($slugs as $slug => $attrs) {
            if (!DB::table('permissions')->where('slug', $slug)->exists()) {
                DB::table('permissions')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'name' => $attrs['name'],
                    'slug' => $slug,
                    'description' => $attrs['description'],
                    'module' => 'customer-disbursements',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (!Schema::hasTable('customer_disbursements')) {
            Schema::create('customer_disbursements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('customer_id');
                $table->uuid('sale_id')->nullable();
                $table->decimal('amount', 10, 2);
                $table->text('notes')->nullable();
                $table->uuid('disbursed_by');
                $table->string('status')->default('pending');
                $table->string('disbursement_phone')->nullable();
                $table->uuid('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
                $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
                $table->foreign('disbursed_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'total_disbursed')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('total_disbursed', 12, 2)->default(0)->after('is_active');
            });
        }

        if (Schema::hasTable('tickets') && !Schema::hasColumn('tickets', 'disbursement_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->uuid('disbursement_id')->nullable()->after('branch_id');
                $table->foreign('disbursement_id')->references('id')->on('customer_disbursements')->onDelete('set null');
            });
        }
    }
};
