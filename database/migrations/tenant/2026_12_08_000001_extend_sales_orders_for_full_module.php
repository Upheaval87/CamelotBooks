<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SALES ORDERS MODULE — §5.1 sanctioned migration (nullable-safe).
 *
 * Every column is additive and guarded with hasColumn() so this runs cleanly on
 * both a fresh tenant database and a live tenant that already carries the base
 * `sales_orders` / `sales_order_lines` tables from
 * 2026_11_10_000001_create_sales_orders_tables.
 *
 * Column-name reconciliation against the spec's contract. Where the existing
 * schema already carries a column with equivalent meaning we REUSE it rather
 * than adding a second source of truth:
 *
 *   spec `warehouse_id`      → existing `branch_id`      (Branch is the warehouse)
 *   spec `cost_centre`       → existing `cost_center_id`
 *   spec `subtotal`          → existing `amount`
 *   spec `grand_total`       → existing `total`
 *   spec `notes_customer`    → existing `memo`
 *   spec `delivery_date`     → existing `expected_delivery_date`
 *   spec `invoice_id`        → existing `converted_invoice_id`
 *   spec `order_id`          → existing `sales_order_id`
 *   spec `qty_ordered`       → existing `quantity`
 *   spec `customer_id`       → stays NOT NULL: R10 creates the walk-in customer
 *                             row inside the same transaction and links it, so
 *                             an order always resolves to a customer record.
 *                             `customer_name` is stored as the print snapshot.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── sales_orders ────────────────────────────────────────────────────
        if (Schema::hasTable('sales_orders')) {
            $this->addColumns('sales_orders', [
                'source_quotation_id' => 'foreignId',
                'customer_name' => 'string:200',
                'contact_person' => 'string:150',
                'email' => 'string:150',
                'phone' => 'string:50',
                'delivery_address' => 'text',
                'delivery_method' => 'string:40',
                'department' => 'string:120',
                'prepared_by' => 'string:150',
                'terms_key' => 'string:60',
                'notes_internal' => 'text',
                'delivered_on' => 'date',
                'delivery_note_no' => 'string:30',
                'received_by' => 'string:150',
                'emailed_to' => 'string:150',
                'emailed_at' => 'timestamp',
                'cancel_reason' => 'text',
                'sent_at' => 'timestamp',
                'confirmed_at' => 'timestamp',
                'fulfilled_at' => 'timestamp',
            ]);
        }

        // ── sales_order_lines ───────────────────────────────────────────────
        if (Schema::hasTable('sales_order_lines')) {
            $this->addColumns('sales_order_lines', [
                'position' => 'int:0',
                'item_code' => 'string:60',
                'item_name' => 'string:200',
                'qty_fulfilled' => 'decimal',
            ]);
        }

        // ── order_fulfilments ──────────────────────────────────────────────
        if (! Schema::hasTable('order_fulfilments')) {
            Schema::create('order_fulfilments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->index();
                $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
                $table->foreignId('sales_order_line_id')->constrained('sales_order_lines')->cascadeOnDelete();
                $table->decimal('quantity', 12, 2)->default(0);
                $table->date('fulfil_date');
                $table->string('delivery_note_no', 30)->nullable();
                $table->string('received_by', 150)->nullable();
                $table->foreignId('created_by')->nullable();
                $table->timestamps();

                $table->index(['sales_order_id', 'fulfil_date']);
                $table->index(['company_id', 'delivery_note_no']);
            });
        }

        // ── stock_movements (§5.1 reservation/relief ledger) ───────────────
        // The spec keys sales-order stock integration off a `stock_movements`
        // ledger. No such table exists in the stock master (the inventory
        // module owns inventory_cost_layers / inventory_adjustments /
        // inventory_transfers instead), so this module defines the ledger itself
        // rather than reaching into the stock master. It is sales-order scoped:
        // a `reservation` row on confirm (on-hand unchanged, goods have not
        // moved) and a `relief` row on fulfilment (mirrors the cost-layer
        // consumption already performed by InventoryService::consumeStock).
        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('type', 30);                 // reservation | relief
                $table->decimal('quantity', 12, 2);          // always positive
                $table->string('signed_quantity', 32)->nullable(); // ±qty per spec
                $table->string('item_code', 60)->nullable();
                $table->string('reference', 60)->nullable(); // order or DN number
                $table->string('reference_type', 190)->nullable();
                $table->foreignId('reference_id')->nullable();
                $table->date('movement_date');
                $table->string('memo')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'type']);
                $table->index(['company_id', 'product_id', 'movement_date']);
                $table->index(['reference_type', 'reference_id']);
            });
        }

        // ── numbering sequence for delivery notes (DN-{YYYY}-{seq4}) ────────
        if (Schema::hasTable('numbering_sequences')) {
            $hasSequence = \Illuminate\Support\Facades\DB::table('numbering_sequences')
                ->where('document_type', 'delivery_note')
                ->exists();

            if (! $hasSequence) {
                // One row PER company — a single insert with a Collection of
                // ids would bind a Collection to company_id, so loop and skip
                // any company that already has the sequence.
                $companyIds = \Illuminate\Support\Facades\DB::table('companies')
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();

                foreach ($companyIds as $companyId) {
                    $already = \Illuminate\Support\Facades\DB::table('numbering_sequences')
                        ->where('company_id', $companyId)
                        ->where('document_type', 'delivery_note')
                        ->exists();

                    if ($already) {
                        continue;
                    }

                    \Illuminate\Support\Facades\DB::table('numbering_sequences')->insert([
                        'company_id' => $companyId,
                        'document_type' => 'delivery_note',
                        'prefix' => 'DN-',
                        'padding_width' => 4,
                        'next_number' => 1,
                        'reset_policy' => 'annually',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fulfilments');
        Schema::dropIfExists('stock_movements');

        foreach ([
            'sent_at', 'confirmed_at', 'fulfilled_at', 'cancel_reason', 'emailed_at',
            'emailed_to', 'received_by', 'delivery_note_no', 'delivered_on',
            'notes_internal', 'terms_key', 'prepared_by', 'department',
            'delivery_method', 'delivery_address', 'phone', 'email', 'contact_person',
            'customer_name', 'source_quotation_id',
        ] as $column) {
            if (Schema::hasColumn('sales_orders', $column)) {
                Schema::table('sales_orders', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }

        foreach (['qty_fulfilled', 'item_name', 'item_code', 'position'] as $column) {
            if (Schema::hasColumn('sales_order_lines', $column)) {
                Schema::table('sales_order_lines', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }
    }

    /**
     * @param  array<string,string>  $columns  column => spec alias
     *                                     `foreignId` `text` `date` `timestamp`
     *                                     `int:{default}` `decimal` `string:{len}`
     */
    private function addColumns(string $table, array $columns): void
    {
        foreach ($columns as $name => $alias) {
            if (Schema::hasColumn($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($name, $alias) {
                if ($alias === 'foreignId') {
                    $t->foreignId($name)->nullable();

                    return;
                }

                if ($alias === 'text') {
                    $t->text($name)->nullable();

                    return;
                }

                if ($alias === 'date') {
                    $t->date($name)->nullable();

                    return;
                }

                if ($alias === 'timestamp') {
                    $t->timestamp($name)->nullable();

                    return;
                }

                if ($alias === 'decimal') {
                    $t->decimal($name, 12, 2)->default(0);

                    return;
                }

                if (str_starts_with($alias, 'int:')) {
                    $t->integer($name)->default((int) substr($alias, 4));

                    return;
                }

                if (str_starts_with($alias, 'string:')) {
                    $t->string($name, (int) substr($alias, 7))->nullable();

                    return;
                }

                $t->string($name)->nullable();
            });
        }
    }
};