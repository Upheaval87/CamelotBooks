<?php

namespace App\Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Http\Request;

class VendorNoteController extends Controller
{
    public function store(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'type' => 'required|in:internal,reminder,follow_up',
            'title' => 'required|string',
            'body' => 'required|string',
            'reminder_date' => 'nullable|date',
            'pinned' => 'nullable|boolean',
        ]);

        $vendor->notes()->create([
            'company_id' => $vendor->company_id,
            'type' => $data['type'],
            'title' => $data['title'],
            'body' => $data['body'],
            'reminder_date' => $data['reminder_date'] ?? null,
            'pinned' => $request->boolean('pinned'),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Note added successfully.');
    }
}
