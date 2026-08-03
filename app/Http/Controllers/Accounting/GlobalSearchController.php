<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\FeatureManagement;
use App\Services\Search\SearchCatalog;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __construct(private readonly SearchCatalog $catalog)
    {
    }

    /**
     * Mode 1 — scoped inline search for a single entity type.
     * Returns a plain JSON array of rows shaped by the catalog entry.
     */
    public function entity(Request $request, string $entity)
    {
        $companyId = session('current_company_id');
        $entry = $this->catalog->find($entity);

        abort_unless($entry, 404);
        abort_unless($request->user()->can($entry['permission']), 403);

        if ($entry['feature'] && !FeatureManagement::isEnabled($companyId, $entry['feature'])) {
            abort(404);
        }

        $q = (string) $request->input('q', '');
        $limit = min((int) $request->input('limit', 15), 50);

        return response()->json($entry['search']($q, $companyId, $limit, null));
    }

    /**
     * Mode 2 — global search across every entity type the user is
     * permitted to search in the active company. Returns grouped results.
     */
    public function global(Request $request)
    {
        $user = $request->user();
        $companyId = session('current_company_id');
        $q = (string) $request->input('q', '');
        $onlyEntity = (string) $request->input('entity', '');

        $entries = $this->catalog->permittedFor($user, $companyId);

        if ($onlyEntity !== '') {
            $entries = array_values(array_filter($entries, fn (array $e) => $e['key'] === $onlyEntity));
        }

        $limit = min((int) $request->input('limit', 6), 20);
        $groups = [];

        foreach ($entries as $entry) {
            $rows = $entry['search']($q, $companyId, $limit, null);
            if ($rows->isEmpty()) {
                continue;
            }

            $groups[] = [
                'key' => $entry['key'],
                'label' => $entry['label'],
                'icon' => $entry['icon'],
                'results' => $rows->map(fn (array $row) => array_merge($row, [
                    'type' => $entry['key'],
                    'icon' => $entry['icon'],
                    'title' => $row['label'],
                    'subtitle' => $row['subtitle'] ?? '',
                    'url' => $row['url'] ?? null,
                ]))->values(),
            ];
        }

        return response()->json($groups);
    }
}
