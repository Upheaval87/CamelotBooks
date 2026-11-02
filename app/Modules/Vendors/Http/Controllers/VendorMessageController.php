<?php

namespace App\Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Http\Request;

class VendorMessageController extends Controller
{
    public function store(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'channel' => 'required|in:email,sms',
            'subject' => 'required|string',
            'body' => 'required|string',
            'template_key' => 'nullable|string',
        ]);

        $vendor->messages()->create([
            'company_id' => $vendor->company_id,
            'direction' => 'outbound',
            'channel' => $data['channel'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'template_key' => $data['template_key'] ?? null,
            'sent_by' => auth()->id(),
        ]);

        return back()->with('success', 'Message sent successfully.');
    }
}
