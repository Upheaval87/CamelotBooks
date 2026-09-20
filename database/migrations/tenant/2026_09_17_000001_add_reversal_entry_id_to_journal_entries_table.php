<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('journal_entries', 'reversal_entry_id')) {
                $table->foreignId('reversal_entry_id')
                    ->nullable()
                    ->after('linked_entry_id')
                    ->constrained('journal_entries')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entries', 'reversal_entry_id')) {
                $table->dropConstrainedForeignId('reversal_entry_id');
            }
        });
    }
};