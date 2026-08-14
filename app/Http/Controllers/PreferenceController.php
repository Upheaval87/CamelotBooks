<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    /**
     * Persist the signed-in user's UI text-size preset ('sm'|'md'|'lg').
     * Central (not tenant-scoped): a UI preference that follows the user across
     * every company and the super-admin panel.
     */
    public function updateTextSize(Request $request)
    {
        $validated = $request->validate([
            'size' => ['required', 'string', Rule::in(array_keys(User::TEXT_SIZES))],
        ]);

        $request->user()->update(['text_size' => $validated['size']]);

        return response()->json([
            'ok' => true,
            'size' => $validated['size'],
        ]);
    }
}
