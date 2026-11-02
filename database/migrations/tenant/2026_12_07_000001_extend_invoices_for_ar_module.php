<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * INVOICES MODULE · 5.1 — AR module schema, additive and nullable-safe.
 *
 * Everything here is additive on top of the legacy invoice schema (see
 * 2026_02_01_000050 / 000060): legacy rows keep working untouched and every new
 * column is nullable so no backfill is required.
 *
 * Two intentional reconciliations with the spec column list:
 *
 *  - `number`      -> the table already stores `invoice_number` (unique per
 *                    company); every consumer outside this module reads that
 *                    name, so it stays the source of truth rather than adding a
 *                    duplicate column that could drift.
 *  - `qty`         -> likewise `invoice_lines.quantity` already holds the
 *                    quantity and drives the GL posting maths, so no duplicate
 *                    `qty` column is introduced.
 *  - `issue_date`  -> added as a nullable alias of the existing `invoice_date`
 *                    and written in lockstep by the AR service, because reports
 *                    and statements in other modules order by `invoice_date`.
 *  - `status`      -> the stored vocabulary stays `partially_paid` so the
 *                    existing readers (dashboard, customer centre, statements,
 *                    sales register, receipts) keep resolving. `overdue` is
 *    derived-only per R6 and is never written by the AR module; the model
 *    normalises the legacy value on read.
 *
 * `description` becomes nullable because 8.3 requires a line whose description
 * is blank to still count as a complete line for the Post gate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Walk-in capture (R12) means an invoice may exist without a
            // registered customer account row.
            $table->unsignedBigInteger('customer_id')->nullable()->change();

            $this->addIfMissing($table, 'customer_name', fn (Blueprint $t) => $t->string('customer_name', 150)->nullable()->after('customer_id'));
            $this->addIfMissing($table, 'contact_person', fn (Blueprint $t) => $t->string('contact_person', 150)->nullable()->after('customer_name'));
            $this->addIfMissing($table, 'email', fn (Blueprint $t) => $t->string('email', 150)->nullable()->after('contact_person'));
            $this->addIfMissing($table, 'phone', fn (Blueprint $t) => $t->string('phone', 60)->nullable()->after('email'));
            $this->addIfMissing($table, 'bill_address', fn (Blueprint $t) => $t->text('bill_address')->nullable()->after('phone'));
            $this->addIfMissing($table, 'terms_key', fn (Blueprint $t) => $t->string('terms_key', 60)->nullable()->after('bill_address'));
            $this->addIfMissing($table, 'customer_ref', fn (Blueprint $t) => $t->string('customer_ref', 100)->nullable()->after('terms_key'));

            // Source lineage: manual | quotation | sales_order
            $this->addIfMissing($table, 'source_type', fn (Blueprint $t) => $t->string('source_type', 20)->nullable()->after('customer_ref'));
            $this->addIfMissing($table, 'source_ref', fn (Blueprint $t) => $t->string('source_ref', 60)->nullable()->after('source_type'));
            $this->addIfMissing($table, 'source_quotation_id', function (Blueprint $t) {
                $t->foreignId('source_quotation_id')->nullable()->after('source_ref')
                    ->constrained('quotations')->nullOnDelete();
            });

            $this->addIfMissing($table, 'issue_date', fn (Blueprint $t) => $t->date('issue_date')->nullable()->after('invoice_date'));
            $this->addIfMissing($table, 'subtotal', fn (Blueprint $t) => $t->decimal('subtotal', 15, 2)->nullable()->after('amount_paid'));
            $this->addIfMissing($table, 'tax_total', fn (Blueprint $t) => $t->decimal('tax_total', 15, 2)->nullable()->after('subtotal'));
            $this->addIfMissing($table, 'grand_total', fn (Blueprint $t) => $t->decimal('grand_total', 15, 2)->nullable()->after('tax_total'));

            $this->addIfMissing($table, 'notes_customer', fn (Blueprint $t) => $t->text('notes_customer')->nullable()->after('grand_total'));
            $this->addIfMissing($table, 'notes_internal', fn (Blueprint $t) => $t->text('notes_internal')->nullable()->after('notes_customer'));
            $this->addIfMissing($table, 'cost_centre', fn (Blueprint $t) => $t->string('cost_centre', 120)->nullable()->after('notes_internal'));
            $this->addIfMissing($table, 'department', fn (Blueprint $t) => $t->string('department', 120)->nullable()->after('cost_centre'));
            $this->addIfMissing($table, 'prepared_by', fn (Blueprint $t) => $t->string('prepared_by', 120)->nullable()->after('department'));

            $this->addIfMissing($table, 'emailed_to', fn (Blueprint $t) => $t->string('emailed_to', 150)->nullable()->after('prepared_by'));
            $this->addIfMissing($table, 'emailed_at', fn (Blueprint $t) => $t->timestamp('emailed_at')->nullable()->after('emailed_to'));

            // Indexes are guarded too. The column guards above make a re-run
            // against an already-migrated tenant a no-op, but an unguarded
            // `index()` aborts the whole migration with
            // "Duplicate key name 'invoices_co_issue_idx'" — which would leave a
            // renamed/re-run migration permanently unrecordable.
            $this->addIndexIfMissing('invoices', ['company_id', 'issue_date'], 'invoices_co_issue_idx');
            $this->addIndexIfMissing('invoices', ['company_id', 'source_type'], 'invoices_co_source_idx');
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            // 8.3: a blank description still counts as a complete line.
            $table->text('description')->nullable()->change();

            $this->addIfMissing($table, 'position', fn (Blueprint $t) => $t->unsignedInteger('position')->nullable()->after('invoice_id'));
            $this->addIfMissing($table, 'item_code', fn (Blueprint $t) => $t->string('item_code', 60)->nullable()->after('position'));
            $this->addIfMissing($table, 'item_name', fn (Blueprint $t) => $t->string('item_name', 200)->nullable()->after('item_code'));
        });

        // Immutable receipts. No update or delete path exists anywhere in the
        // module; a mistaken receipt is corrected by a credit note (later).
        if (!Schema::hasTable('invoice_payments')) {
            Schema::create('invoice_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->string('receipt_no', 30);
                $table->date('paid_on');
                $table->string('method', 20);
                $table->string('method_label', 80)->nullable();
                $table->decimal('amount', 15, 2);
                $table->text('allocation_note')->nullable();
                $table->json('details')->nullable();
                $table->json('splits')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'receipt_no']);
                $table->index(['invoice_id', 'paid_on']);
                $table->index(['company_id', 'method']);
            });
        }

        // Quotation picker source of truth (R13).
        Schema::table('quotations', function (Blueprint $table) {
            $this->addIfMissing($table, 'invoiced_flag', fn (Blueprint $t) => $t->boolean('invoiced_flag')->default(false)->after('status'));
            $this->addIfMissing($table, 'invoice_id', function (Blueprint $t) {
                $t->foreignId('invoice_id')->nullable()->after('invoiced_flag')
                    ->constrained('invoices')->nullOnDelete();
            });
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
            if (Schema::hasColumn('quotations', 'invoiced_flag')) {
                $table->dropColumn('invoiced_flag');
            }
        });

        Schema::dropIfExists('invoice_payments');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_co_issue_idx');
            $table->dropIndex('invoices_co_source_idx');
        });

        foreach (['emailed_at', 'emailed_to', 'prepared_by', 'department', 'cost_centre',
                  'notes_internal', 'notes_customer', 'grand_total', 'tax_total', 'subtotal',
                  'issue_date', 'source_quotation_id', 'source_ref', 'source_type',
                  'customer_ref', 'terms_key', 'bill_address', 'phone', 'email',
                  'contact_person', 'customer_name'] as $column) {
            if (Schema::hasColumn('invoices', $column)) {
                Schema::table('invoices', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }

        foreach (['item_name', 'item_code', 'position'] as $column) {
            if (Schema::hasColumn('invoice_lines', $column)) {
                Schema::table('invoice_lines', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }
    }

    /**
     * Guarded additive column: an already-applied migration must never be
     * edited (see the bank-reconciliation schema-drift lesson), so each new
     * column is created only when the target database does not have it yet.
     */
    private function addIfMissing(Blueprint $table, string $column, callable $definition): void
    {
        if (!Schema::hasColumn($table->getTable(), $column)) {
            $definition($table);
        }
    }

    /**
     * Guarded index, for the same re-run reason as `addIfMissing`.
     *
     * Declared as a separate `Schema::table` call because a guard cannot be
     * expressed inside the closure that shares the column definitions: by the
     * time the closure runs, the queued commands have not executed yet, so
     * `hasIndex()` would still answer from the pre-migration schema.
     */
    private function addIndexIfMissing(string $table, array $columns, string $name): void
    {
        if (!Schema::hasIndex($table, $name)) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                $blueprint->index($columns, $name);
            });
        }
    }
};