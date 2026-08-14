<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Super-admin panel. Phase 3 placeholder — routes live OUTSIDE the tenant group
 * so no tenant connection is ever bound here. Phase 4 implements the full panel.
 */
class PanelController extends Controller
{
    public function index(): View
    {
        return view('panel.index', ['user' => request()->user()]);
    }
}
