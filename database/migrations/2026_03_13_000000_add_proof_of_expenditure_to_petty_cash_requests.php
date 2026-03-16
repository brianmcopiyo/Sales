<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applicant must upload proof of expenditure after disbursement before they can request again.
     */
    public function up(): void
    {
        Schema::table('petty_cash_requests', function (Blueprint $table) {
            $table->string('proof_of_expenditure_path', 500)->nullable()->after('receipt_attachment_path');
            $table->timestamp('proof_of_expenditure_uploaded_at')->nullable()->after('proof_of_expenditure_path');
        });
    }

    public function down(): void
    {
        Schema::table('petty_cash_requests', function (Blueprint $table) {
            $table->dropColumn(['proof_of_expenditure_path', 'proof_of_expenditure_uploaded_at']);
        });
    }
};
