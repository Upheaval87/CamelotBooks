<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->string('status', 30)->default('draft')->change();
            $table->unsignedBigInteger('branch_id')->nullable()->after('pension_scheme_id');
            $table->timestamp('posted_at')->nullable()->after('approved_by');
            $table->timestamp('paid_at')->nullable()->after('posted_at');
            $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->enum('status', ['draft', 'calculated', 'approved', 'posted', 'partially_paid', 'fully_paid'])->default('draft')->change();
            $table->dropColumn(['branch_id', 'posted_at', 'paid_at', 'paid_by']);
        });
    }
};
