<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'pos_cashier_pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pos_cashier_pin', 255)->nullable()->change();
            });
        }

        $users = DB::table('users')
            ->whereNotNull('pos_cashier_pin')
            ->where('pos_cashier_pin', '!=', '')
            ->get();

        foreach ($users as $user) {
            $pin = $user->pos_cashier_pin;
            if (!str_starts_with($pin, '$2y$') && !str_starts_with($pin, '$2b$') && !str_starts_with($pin, '$argon')) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['pos_cashier_pin' => Hash::make($pin)]);
            }
        }
    }

    public function down(): void
    {
        // Cannot reverse hashing — PINs must be re-set by users
    }
};
