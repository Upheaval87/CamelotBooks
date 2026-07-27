<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Services\Vendor\VendorCentreService;

class VendorCentreController extends Controller
{
    public function __construct(protected VendorCentreService $vendorCentreService)
    {
    }

    public function index()
    {
        $companyId = session('current_company_id');

        $vendors = $this->vendorCentreService->getVendorSummary($companyId);

        return view('accounting.vendor-centre.index', compact('vendors'));
    }

    public function show(Vendor $vendor)
    {
        $companyId = session('current_company_id');
        abort_unless($vendor->company_id == $companyId, 403);

        $timeline = $this->vendorCentreService->getVendorTimeline($vendor, $companyId);
        $stats = $this->vendorCentreService->getVendorStats($vendor, $companyId);

        return view('accounting.vendor-centre.show', compact('vendor', 'timeline', 'stats'));
    }
}
