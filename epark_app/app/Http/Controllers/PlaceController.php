<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public function index()
    {
        // Affiche toutes les places disponibles
        $places = \App\Models\Place::where('disponible', true)->with('user')->get();

        return view('places.index', compact('places'));
    }

    public function create()
    {
        $sites = auth()->user() ? auth()->user()->sites()->get() : collect();
        return view('places.create', compact('sites'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'nom' => 'required|string|max:255',
            'caracteristiques' => 'nullable|string|max:1000',
        ]);

        \App\Models\Place::create([
            'user_id' => $request->user()->id,
            'site_id' => $validated['site_id'],
            'nom' => $validated['nom'],
            'caracteristiques' => $validated['caracteristiques'] ?? null,
            'disponible' => true,
        ]);

        return redirect()->route('dashboard')->with('success', 'Place ajoutée avec succès');
    }

    // Affiche les places proposées par l'utilisateur connecté
    public function mesPlaces()
    {
        $places = \App\Models\Place::where('user_id', auth()->id())->get();

        return view('places.mes', compact('places'));
    }
}
