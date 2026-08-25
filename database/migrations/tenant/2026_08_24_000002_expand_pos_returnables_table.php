<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_returnables')) {
            return;
        }

        Schema::table('pos_returnables', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_returnables', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('pos_returnables', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('pos_returnables', 'intake_number')) {
                $table->string('intake_number', 50)->nullable()->after('branch_id')->unique();
            }
            if (!Schema::hasColumn('pos_returnables', 'bottle_count')) {
                $table->integer('bottle_count')->default(1)->after('quantity');
            }
            if (!Schema::hasColumn('pos_returnables', 'value_each')) {
                $table->decimal('value_each', 8, 2)->default(0)->after('credit_amount');
            }
            if (!Schema::hasColumn('pos_returnables', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('value_each');
            }
            if (!Schema::hasColumn('pos_returnables', 'redeemed_qty')) {
                $table->integer('redeemed_qty')->default(0)->after('expiry_date');
            }
            if (!Schema::hasColumn('pos_returnables', 'redeemed_at')) {
                $table->timestamp('redeemed_at')->nullable()->after('redeemed_qty');
            }
        });

        // Backfill intake_number from brr_number for existing rows
        try {
            $conn = DB::connection(config('tenancy.routing.connection_override') ?: 'tenant');
            $conn->table('pos_returnables')
                ->whereNull('intake_number')
                ->whereNotNull('brr_number')
                ->update(['intake_number' => DB::raw('brr_number')]);
        } catch (\Throwable) {
            // Connection unavailable (central DB migration)
        }

        // Add composite indexes (idempotent)
        Schema::table('pos_returnables', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'expiry_date']);
            $table->index(['company_id', 'customer_id', 'status']);
            $table->index(['company_id', 'branch_id', 'status']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pos_returnables')) {
            return;
        }

        Schema::table('pos_returnables', function (Blueprint $table) {
            $table->dropColumn([
                'customer_id', 'branch_id', 'intake_number', 'bottle_count',
                'value_each', 'expiry_date', 'redeemed_qty', 'redeemed_at',
            ]);
        });
    }
};
