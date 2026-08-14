<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->string('priority', 20)->nullable()->default('normal')->after('status');
            $table->date('required_by')->nullable()->after('date');
            $table->foreignId('requested_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->string('department', 100)->nullable()->after('requested_by');
            $table->string('supplier', 200)->nullable()->after('department');
            $table->foreignId('converted_to_po_id')->nullable()->after('approved_at')->constrained('purchase_orders')->nullOnDelete();
            $table->text('rejected_reason')->nullable()->after('converted_to_po_id');
            $table->timestamp('submitted_at')->nullable()->after('rejected_reason');
            $table->timestamp('converted_at')->nullable()->after('submitted_at');
            $table->string('reference', 60)->nullable()->after('converted_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_to_po_id');
            $table->dropConstrainedForeignId('requested_by');
            $table->dropColumn([
                'priority',
                'required_by',
                'department',
                'supplier',
                'rejected_reason',
                'submitted_at',
                'converted_at',
                'reference',
            ]);
        });
    }
};
