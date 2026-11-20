<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $query = Site::query()
            ->with('user:id,name')
            ->withCount('places')
            ->orderBy('nom');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $sites = $query->get();

        return view('sites.index', compact('sites'));
    }

    public function create(): View
    {
        // Formulaire création site
        return view('sites.create');
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sites', 'nom')->where('user_id', $user->id),
            ],
            'adresse' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ], [
            'nom.unique' => 'Vous avez déjà un site portant ce nom.',
        ]);

        $site = Site::create([
            'nom' => $validated['nom'],
            'adresse' => $validated['adresse'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'user_id' => $user->id,
        ]);

        $this->auditLogger->log('create', 'site', $site->id, null, [
            'nom' => $site->nom,
            'adresse' => $site->adresse,
        ], $user->id);

        return redirect()->route('sites.index')->with('success', 'Site créé avec succès.');
    }

    public function edit(Request $request, Site $site): View
    {
        $this->authorizeOwner($request, $site);

        return view('sites.edit', compact('site'));
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $this->authorizeOwner($request, $site);

        $validated = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sites', 'nom')
                    ->where('user_id', $site->user_id)
                    ->ignore($site->id),
            ],
            'adresse' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ], [
            'nom.unique' => 'Vous avez déjà un site portant ce nom.',
        ]);

        $before = ['nom' => $site->nom, 'adresse' => $site->adresse];

        $site->update([
            'nom' => $validated['nom'],
            'adresse' => $validated['adresse'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        $this->auditLogger->log('update', 'site', $site->id, $before, [
            'nom' => $site->nom,
            'adresse' => $site->adresse,
        ]);

        return redirect()->route('sites.index')->with('success', 'Site mis à jour avec succès.');
    }

    public function destroy(Request $request, Site $site): RedirectResponse
    {
        $this->authorizeOwner($request, $site);

        if ($site->places()->exists()) {
            return back()->withErrors([
                'site' => 'Impossible de supprimer ce site : des places actives y sont encore rattachées.',
            ]);
        }

        $before = ['nom' => $site->nom, 'adresse' => $site->adresse];
        $siteId = $site->id;

        User::where('favorite_site_id', $site->id)->update(['favorite_site_id' => null]);
        $site->delete();

        $this->auditLogger->log('delete', 'site', $siteId, $before, null);

        return redirect()->route('sites.index')->with('success', 'Site supprimé avec succès.');
    }

    private function authorizeOwner(Request $request, Site $site): void
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isAdmin() && $site->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }
}
