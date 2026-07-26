<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AssemblyBuild;
use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Services\Inventory\AssemblyBuildService;
use Illuminate\Http\Request;

class AssemblyController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $builds = AssemblyBuild::where('company_id', $companyId)
            ->with(['assemblyProduct', 'billOfMaterial', 'creator'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('accounting.assemblies.index', compact('builds'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $products = Product::where('company_id', $companyId)
            ->where('is_assembly', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $boms = BillOfMaterial::where('company_id', $companyId)
            ->active()
            ->with('assemblyProduct')
            ->orderBy('bom_number')
            ->get();

        return view('accounting.assemblies.create', compact('products', 'boms'));
    }

    public function store(Request $request, AssemblyBuildService $service)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'assembly_product_id' => 'required|exists:products,id',
            'bom_id' => 'nullable|exists:bill_of_materials,id',
            'date' => 'required|date',
            'quantity' => 'required|numeric|min:0.0001',
            'memo' => 'nullable|string|max:500',
        ]);

        $validated['company_id'] = $companyId;

        try {
            $build = $service->build($validated, $userId);

            return redirect()->route('accounting.assemblies.show', $build)
                ->with('success', "Build {$build->build_number} completed successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function createUnbuild()
    {
        $companyId = session('current_company_id');

        $products = Product::where('company_id', $companyId)
            ->where('is_assembly', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $boms = BillOfMaterial::where('company_id', $companyId)
            ->active()
            ->with('assemblyProduct')
            ->orderBy('bom_number')
            ->get();

        return view('accounting.assemblies.unbuild', compact('products', 'boms'));
    }

    public function storeUnbuild(Request $request, AssemblyBuildService $service)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'assembly_product_id' => 'required|exists:products,id',
            'bom_id' => 'nullable|exists:bill_of_materials,id',
            'date' => 'required|date',
            'quantity' => 'required|numeric|min:0.0001',
            'memo' => 'nullable|string|max:500',
        ]);

        $validated['company_id'] = $companyId;

        try {
            $build = $service->unbuild($validated, $userId);

            return redirect()->route('accounting.assemblies.show', $build)
                ->with('success', "Unbuild {$build->build_number} completed successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(AssemblyBuild $build)
    {
        $companyId = session('current_company_id');

        if ($build->company_id !== $companyId) {
            abort(404);
        }

        $build->load(['assemblyProduct', 'billOfMaterial.lines.componentProduct', 'journalEntry', 'creator']);

        return view('accounting.assemblies.show', compact('build'));
    }

    public function boms(Request $request)
    {
        $companyId = session('current_company_id');

        $boms = BillOfMaterial::where('company_id', $companyId)
            ->withCount('lines')
            ->with('assemblyProduct')
            ->orderBy('bom_number')
            ->paginate(20);

        return view('accounting.assemblies.boms', compact('boms'));
    }

    public function createBom()
    {
        $companyId = session('current_company_id');

        $assemblyProducts = Product::where('company_id', $companyId)
            ->where('is_assembly', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $componentProducts = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.assemblies.create-bom', compact('assemblyProducts', 'componentProducts'));
    }

    public function storeBom(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'assembly_product_id' => 'required|exists:products,id',
            'bom_number' => 'required|string|max:50',
            'name' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.component_product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_of_measure' => 'nullable|string|max:50',
        ]);

        if (BillOfMaterial::where('company_id', $companyId)->where('bom_number', $validated['bom_number'])->exists()) {
            return back()->withErrors(['bom_number' => 'BOM number already exists.'])->withInput();
        }

        $bom = BillOfMaterial::create([
            'company_id' => $companyId,
            'assembly_product_id' => $validated['assembly_product_id'],
            'bom_number' => $validated['bom_number'],
            'name' => $validated['name'] ?? null,
            'estimated_cost' => 0,
            'is_active' => true,
        ]);

        foreach ($validated['lines'] as $line) {
            BillOfMaterialLine::create([
                'bom_id' => $bom->id,
                'component_product_id' => $line['component_product_id'],
                'quantity' => $line['quantity'],
                'unit_of_measure' => $line['unit_of_measure'] ?? null,
            ]);
        }

        return redirect()->route('accounting.assemblies.boms')
            ->with('success', 'Bill of materials created successfully.');
    }

    public function history(Request $request)
    {
        $companyId = session('current_company_id');

        $query = AssemblyBuild::where('company_id', $companyId)
            ->with(['assemblyProduct', 'creator'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('assembly_product_id')) {
            $query->where('assembly_product_id', $request->input('assembly_product_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $builds = $query->paginate(20);

        $products = Product::where('company_id', $companyId)
            ->where('is_assembly', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.assemblies.history', compact('builds', 'products'));
    }
}
