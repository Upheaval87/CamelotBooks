<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\SuperAdminAuditLog;
use Illuminate\Http\Request;

class CurrenciesController extends Controller
{
    public function index()
    {
        $currencies = Currency::query()->ordered()->get();

        return view('superadmin.currencies.index', compact('currencies'));
    }

    public function create()
    {
        return view('superadmin.currencies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:currencies,code',
            'name' => 'required|string|max:120',
            'symbol' => 'nullable|string|max:12',
            'symbol_position' => 'required|in:before,after',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $currency = Currency::create($validated + [
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_CURRENCY_CREATED,
            null,
            'currency',
            $currency->id,
            null,
            $currency->only(['code', 'name', 'symbol', 'symbol_position', 'is_active']),
            "Currency '{$currency->code}' added."
        );

        return redirect()->route('superadmin.currencies.index')
            ->with('success', "Currency '{$currency->code}' created.");
    }

    public function edit(Currency $currency)
    {
        return view('superadmin.currencies.edit', compact('currency'));
    }

    public function update(Request $request, Currency $currency)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:currencies,code,' . $currency->id,
            'name' => 'required|string|max:120',
            'symbol' => 'nullable|string|max:12',
            'symbol_position' => 'required|in:before,after',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $before = $currency->only(['code', 'name', 'symbol', 'symbol_position', 'is_active']);

        $currency->update($validated + [
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_CURRENCY_UPDATED,
            null,
            'currency',
            $currency->id,
            $before,
            $currency->only(['code', 'name', 'symbol', 'symbol_position', 'is_active']),
            "Currency '{$currency->code}' updated."
        );

        return redirect()->route('superadmin.currencies.index')
            ->with('success', "Currency '{$currency->code}' updated.");
    }

    public function toggle(Request $request, Currency $currency)
    {
        $currency->update(['is_active' => !$currency->is_active]);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_CURRENCY_TOGGLED,
            null,
            'currency',
            $currency->id,
            ['is_active' => !$currency->is_active],
            ['is_active' => $currency->is_active],
            "Currency '{$currency->code}' " . ($currency->is_active ? 'enabled' : 'disabled') . '.'
        );

        return back()->with('success', "Currency '{$currency->code}' " . ($currency->is_active ? 'enabled' : 'disabled') . '.');
    }
}
