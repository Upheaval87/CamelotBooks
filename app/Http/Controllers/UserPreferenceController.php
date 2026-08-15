<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    /**
     * Persist the signed-in user's UI font-scale factor (one of FONT_STEPS).
     * Central (not tenant-scoped): a UI preference that follows the user across
     * every company and the super-admin panel.
     */
    public function updateFontScale(Request $request)
    {
        $request->validate([
            'font_scale' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    // Cast to float so integer 1 (JSON) matches 1.00 strictly.
                    if (! in_array((float) $value, User::FONT_STEPS, true)) {
                        $fail(trans('validation.in', ['attribute' => $attribute]));
                    }
                },
            ],
        ]);

        $scale = (float) $request->input('font_scale');

        $request->user()->update(['font_scale' => $scale]);

        return response()->json([
            'status' => 'ok',
            'font_scale' => $scale,
        ]);
    }
}
