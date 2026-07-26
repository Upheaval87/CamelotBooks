<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosReturn;
use App\Models\PosSale;
use App\Services\POS\PosReturnService;
use Illuminate\Http\Request;

class PosReturnController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');
        $returns = PosReturn::where('company_id', $companyId)
            ->with(['sale', 'terminal'])
            ->latest()
            ->paginate(25);

        return view('pos.returns.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $companyId = session('current_company_id');
        $saleId = $request->query('sale_id');

        $sales = PosSale::where('company_id', $companyId)
            ->where('status', 'posted')
            ->latest()
            ->get(['id', 'sale_number', 'total', 'date', 'customer_id']);

        $sale = null;
        if ($saleId) {
            $sale = PosSale::where('company_id', $companyId)
                ->where('id', $saleId)
                ->with(['lines.product', 'payments.paymentMethod'])
                ->first();

            if (!$sale) {
                return redirect()->route('pos.returns.create')
                    ->with('error', 'Sale not found.');
            }
        }

        return view('pos.returns.create', compact('sales', 'sale'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = $request->user()->id;

        $validated = $request->validate([
            'pos_sale_id' => 'required|exists:pos_sales,id',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:500',
            'lines' => 'required|array|min:1',
            'lines.*.pos_sale_line_id' => 'required|exists:pos_sale_lines,id',
            'lines.*.quantity_returned' => 'required|numeric|min:0.01',
        ]);

        $validated['company_id'] = $companyId;

        try {
            $return = app(PosReturnService::class)->processReturn($validated, $userId);

            return redirect()->route('pos.returns.show', $return)
                ->with('success', "Return {$return->return_number} posted successfully.");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['lines' => $e->getMessage()])->withInput();
        }
    }

    public function show(PosReturn $return)
    {
        $companyId = session('current_company_id');
        abort_unless($return->company_id === $companyId, 403);

        $return->load([
            'sale',
            'lines.product',
            'lines.saleLine',
            'journalEntry.lines.account',
            'creator',
            'poster',
        ]);

        return view('pos.returns.show', compact('return'));
    }
}
