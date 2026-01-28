<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        $sites = \App\Models\Site::with('proprietaire')->get();

        return view('sites.index', compact('sites'));
    }

    public function create()
    {
        // Formulaire création site
        return view('sites.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
        ]);
        $site = \App\Models\Site::create([
            'nom' => $validated['nom'],
            'adresse' => $validated['adresse'],
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('sites.index')->with('success', 'Site créé avec succès');
    }
}
