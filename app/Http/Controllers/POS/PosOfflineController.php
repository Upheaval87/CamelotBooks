<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;

class PosOfflineController extends Controller
{
    public function index()
    {
        return view('pos.offline.index');
    }
}
