<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('tin', 20)->nullable()->after('name');
        });

        Schema::create('eis_terminals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('site_id', 50);
            $table->string('device_serial', 100)->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'revoked'])->default('pending');
            $table->text('jwt_token')->nullable();
            $table->text('secret_key')->nullable();
            $table->text('validation_key')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_submission_at')->nullable();
            $table->boolean('should_block_terminal')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'site_id']);
        });

        Schema::create('eis_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('eis_terminal_id')->constrained('eis_terminals')->cascadeOnDelete();
            $table->string('receipt_number', 50);
            $table->string('invoice_type', 10); // B2B, B2C
            $table->enum('status', ['pending', 'submitted', 'accepted', 'rejected', 'error'])->default('pending');
            $table->json('request_payload');
            $table->json('response_payload')->nullable();
            $table->text('validation_url')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'receipt_number']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eis_submissions');
        Schema::dropIfExists('eis_terminals');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('tin');
        });
    }
};
