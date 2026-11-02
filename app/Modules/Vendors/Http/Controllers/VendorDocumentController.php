<?php

namespace App\Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorDocumentController extends Controller
{
    public function store(Request $request, Vendor $vendor)
    {
        $request->validate([
            'file' => 'required|file|max:25600',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('vendor-documents/' . $vendor->id, 'public');
            $vendor->documents()->create([
                'company_id' => $vendor->company_id,
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Document uploaded successfully.');
    }
}
