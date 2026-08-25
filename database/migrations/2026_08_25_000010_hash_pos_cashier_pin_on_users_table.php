<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
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
