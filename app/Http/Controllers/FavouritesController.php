<?php

namespace App\Http\Controllers;

use App\Services\FavouritesService;
use Illuminate\Http\Request;

class FavouritesController extends Controller
{
    public function __construct(private readonly FavouritesService $favourites)
    {
    }

    /**
     * The current user's favourites + pin preference, as JSON for the
     * front-end store (loaded once on login, kept in sync via the
     * optimistic endpoints below).
     */
    public function index()
    {
        return response()->json([
            'favourites' => $this->favourites->listForUser(auth()->user()),
            'pinned' => $this->favourites->isPinned(auth()->user()),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_key' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:50'],
            'url' => ['required', 'string', 'max:500'],
        ]);

        $row = $this->favourites->add(
            auth()->user(),
            $validated['page_key'],
            $validated['label'],
            $validated['icon'] ?: 'star',
            $validated['url'],
        );

        if ($row === null) {
            return response()->json([
                'error' => __('You have reached the maximum of :count favourites.', ['count' => FavouritesService::MAX_FAVOURITES]),
            ], 422);
        }

        return response()->json(['favourite' => $row->only(['page_key', 'label', 'icon', 'url'])]);
    }

    public function destroy(Request $request, string $pageKey)
    {
        $this->favourites->remove(auth()->user(), $pageKey);

        return response()->json(['ok' => true]);
    }

    /**
     * Registry of all star-able section pages for the "Add" page picker.
     * Route names that resolve without parameters are included.
     */
    public function pages()
    {
        $pages = collect(FavouritesService::PAGES)
            ->map(fn (array $meta, string $routeName) => [
                'page_key' => $meta[0],
                'label' => $meta[1],
                'icon' => $meta[2],
                'url' => route($routeName),
            ])
            ->filter(fn (array $page) => $page['page_key'] !== 'my-tasks')
            ->values();

        return response()->json(['pages' => $pages]);
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'keys' => ['required', 'array'],
            'keys.*' => ['string', 'max:100'],
        ]);

        $this->favourites->reorder(auth()->user(), $validated['keys']);

        return response()->json(['ok' => true]);
    }

    public function preferences(Request $request)
    {
        $validated = $request->validate([
            'sidebar_pinned' => ['required', 'boolean'],
        ]);

        $this->favourites->setPinned(auth()->user(), (bool) $validated['sidebar_pinned']);

        return response()->json(['ok' => true]);
    }
}
