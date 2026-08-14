<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
        Schema::dropIfExists('bank_reconciliations');
    }

    public function down(): void
    {
        // Intentionally empty: the legacy Bank Reconciliation feature was removed
        // permanently (replaced by the new reconciliation module). Recreating the
        // old tables is not supported.
    }
};
