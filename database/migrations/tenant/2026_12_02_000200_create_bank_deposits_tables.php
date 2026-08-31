<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Durable deposit entity over the 1050 Undeposited Funds clearing stream.
        // Additive only - no DROP / TRUNCATE / DELETE of existing data.

        if (! Schema::hasTable('bank_deposits')) {
            Schema::create('bank_deposits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('deposit_no', 40)->index();
                $table->date('deposit_date');
                $table->foreignId('bank_account_id')->constrained('accounts')->cascadeOnDelete();
                $table->string('reference', 255)->nullable();
                $table->string('description', 500)->nullable();
                $table->decimal('total', 15, 2)->default(0);
                $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('posted_by')->nullable()->constrained('users');
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('voided_by')->nullable()->constrained('users');
                $table->timestamp('voided_at')->nullable();
                $table->string('void_reason', 500)->nullable();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->timestamps();
            });

            Schema::table('bank_deposits', function (Blueprint $table) {
                $table->unique(['company_id', 'deposit_no']);
            });
        }

        if (! Schema::hasTable('bank_deposit_lines')) {
            Schema::create('bank_deposit_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deposit_id')->constrained('bank_deposits')->cascadeOnDelete();
                $table->string('source_type', 20)->default('receipt');
                $table->unsignedBigInteger('source_id');
                $table->foreignId('sales_receipt_id')->nullable()->constrained('sales_receipts')->nullOnDelete();
                $table->string('reference', 255)->nullable();
                $table->string('description', 500)->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->timestamps();

                // source_id = the 1050-debit journal_entry_lines.id; unique prevents double-depositing.
                $table->unique(['source_type', 'source_id']);
            });
        }

        // Spec: sales_receipts / cheques ADD deposit_id FK (NULL = undeposited). Additive nullable.
        if (Schema::hasTable('sales_receipts') && ! Schema::hasColumn('sales_receipts', 'deposit_id')) {
            Schema::table('sales_receipts', function (Blueprint $table) {
                $table->foreignId('deposit_id')->nullable()->after('journal_entry_id')
                    ->constrained('bank_deposits')->nullOnDelete();
            });
        }

        // Seed a DEP- numbering sequence per existing company (company_id is NOT NULL + unique on
        // (company_id, document_type), so the recurring-journals-style company-less insert would be
        // silently missed by NumberingSequenceService::getNextNumber()).
        if (Schema::hasTable('numbering_sequences') && Schema::hasTable('companies')) {
            $companyIds = DB::table('companies')->pluck('id');
            $rows = collect($companyIds)->map(fn ($companyId) => [
                'company_id' => $companyId,
                'document_type' => 'deposit',
                'prefix' => 'DEP-',
                'padding_width' => 4,
                'next_number' => 1,
                'reset_policy' => 'annually',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if (! empty($rows)) {
                DB::table('numbering_sequences')->insertOrIgnore($rows);
            }
        }
    }

    public function down(): void
    {
        // Additive-only; drop the new tables/columns in reverse.
        if (Schema::hasTable('sales_receipts') && Schema::hasColumn('sales_receipts', 'deposit_id')) {
            Schema::table('sales_receipts', function (Blueprint $table) {
                $table->dropForeign(['deposit_id']);
                $table->dropColumn('deposit_id');
            });
        }
        Schema::dropIfExists('bank_deposit_lines');
        Schema::dropIfExists('bank_deposits');
    }
};
