<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SiteController extends Controller
{
    public function index(): View
    {
        $sites = \App\Models\Site::with('user')->get();

        return view('sites.index', compact('sites'));
    }

    public function create(): View
    {
        // Formulaire création site
        return view('sites.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
        ]);
        /** @var \App\Models\User $user */
        $user = $request->user();

        $site = \App\Models\Site::create([
            'nom' => $validated['nom'],
            'adresse' => $validated['adresse'],
            'user_id' => $user->id,
        ]);

        return redirect()->route('sites.index')->with('success', 'Site créé avec succès');
    }
}
