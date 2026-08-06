<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 120);
            $table->string('symbol', 12)->nullable();
            $table->string('symbol_position', 6)->default('before');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('currencies')->insertOrIgnore($this->defaults());
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }

    /**
     * Default reference list. Super admins can add/extend/order/reorder these
     * from the Super Admin -> Currencies screen.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function defaults(): array
    {
        $rows = [
            'MWK' => ['Malawian Kwacha', 'MK', 'before'],
            'USD' => ['US Dollar', '$', 'before'],
            'ZAR' => ['South African Rand', 'R', 'before'],
            'GBP' => ['British Pound', '£', 'before'],
            'EUR' => ['Euro', '€', 'before'],
            'ZMW' => ['Zambian Kwacha', 'ZK', 'before'],
            'ZWL' => ['Zimbabwean Dollar', 'Z$', 'before'],
            'BWP' => ['Botswana Pula', 'P', 'before'],
            'TZS' => ['Tanzanian Shilling', 'TSh', 'before'],
            'UGX' => ['Ugandan Shilling', 'USh', 'before'],
            'KES' => ['Kenyan Shilling', 'KSh', 'before'],
            'NGN' => ['Nigerian Naira', '₦', 'before'],
            'GHS' => ['Ghanaian Cedi', 'GH₵', 'before'],
            'PHP' => ['Philippine Peso', '₱', 'before'],
            'INR' => ['Indian Rupee', '₹', 'before'],
            'JPY' => ['Japanese Yen', '¥', 'before'],
            'CNY' => ['Chinese Yuan', '¥', 'before'],
            'CAD' => ['Canadian Dollar', 'C$', 'before'],
            'AUD' => ['Australian Dollar', 'A$', 'before'],
            'CHF' => ['Swiss Franc', 'Fr', 'before'],
        ];

        $now = now()->toDateTimeString();

        return collect($rows)->map(function ($row, $code) use ($now) {
            return [
                'code' => $code,
                'name' => $row[0],
                'symbol' => $row[1],
                'symbol_position' => $row[2],
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();
    }
};
