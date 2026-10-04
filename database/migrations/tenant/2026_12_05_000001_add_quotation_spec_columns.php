<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quotations module rebuild — sanctioned backend contract (§7.1).
 *
 * Additive only: the existing `quotations`/`quotation_lines` columns are
 * load-bearing (reports, PDF presenter, search, invoice/receipt conversion),
 * so this migration ADDS the spec columns that do not exist and maps the
 * overlapping spec concepts onto the existing columns (number→quotation_number,
 * ref_no→reference, grand_total→total, subtotal→amount, quote_date→quotation_date,
 * notes_customer→memo, currency_code→currency, branch→branch_id,
 * cost_centre→cost_center_id). Every step is guarded so it is safe on fresh
 * installs and on already-provisioned tenant databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'customer_name')) {
                $table->string('customer_name', 255)->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('quotations', 'contact_person')) {
                $table->string('contact_person', 255)->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('quotations', 'email')) {
                $table->string('email', 255)->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('quotations', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }
            if (!Schema::hasColumn('quotations', 'terms_key')) {
                $table->string('terms_key', 40)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('quotations', 'department')) {
                $table->string('department', 120)->nullable()->after('cost_center_id');
            }
            if (!Schema::hasColumn('quotations', 'prepared_by')) {
                $table->string('prepared_by', 120)->nullable()->after('department');
            }
            if (!Schema::hasColumn('quotations', 'project')) {
                $table->string('project', 120)->nullable()->after('prepared_by');
            }
            if (!Schema::hasColumn('quotations', 'notes_internal')) {
                $table->text('notes_internal')->nullable()->after('memo');
            }
            if (!Schema::hasColumn('quotations', 'superseded_by')) {
                $table->unsignedBigInteger('superseded_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('quotations', 'emailed_at')) {
                $table->timestamp('emailed_at')->nullable()->after('posted_at');
            }
            if (!Schema::hasColumn('quotations', 'emailed_to')) {
                $table->string('emailed_to', 255)->nullable()->after('emailed_at');
            }
        });

        if (!Schema::hasIndex('quotations', 'quotations_superseded_by_index')) {
            try {
                Schema::table('quotations', function (Blueprint $table) {
                    $table->index('superseded_by', 'quotations_superseded_by_index');
                });
            } catch (\Throwable $e) {
                // Index may already exist under a different name on legacy installs.
            }
        }

        Schema::table('quotation_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('quotation_lines', 'position')) {
                $table->unsignedInteger('position')->default(0)->after('quotation_id');
            }
            if (!Schema::hasColumn('quotation_lines', 'item_code')) {
                $table->string('item_code', 60)->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('quotation_lines', 'item_name')) {
                $table->string('item_name', 255)->nullable()->after('item_code');
            }
            if (!Schema::hasColumn('quotation_lines', 'disc_pct')) {
                $table->decimal('disc_pct', 6, 3)->default(0)->after('unit_price');
            }
        });

        // §7.3 — numbering QT-{YYYY}-{seq4}. Existing tenants carry the old
        // QTN- prefix; align it so the live sequence emits the spec format.
        if (Schema::hasTable('numbering_sequences')) {
            DB::table('numbering_sequences')
                ->where('document_type', 'quotation')
                ->where('prefix', 'QTN-')
                ->update(['prefix' => 'QT-']);
        }
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            foreach (['customer_name', 'contact_person', 'email', 'phone', 'terms_key', 'department', 'prepared_by', 'project', 'notes_internal', 'superseded_by', 'emailed_at', 'emailed_to'] as $column) {
                if (Schema::hasColumn('quotations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('quotation_lines', function (Blueprint $table) {
            foreach (['position', 'item_code', 'item_name', 'disc_pct'] as $column) {
                if (Schema::hasColumn('quotation_lines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
