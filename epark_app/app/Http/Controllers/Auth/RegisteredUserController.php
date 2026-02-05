<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Contracts\View\View;
use App\Models\Site;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $sites = Site::orderBy('nom')->get();

        return view('auth.register', compact('sites'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'favorite_site_id' => ['required'],
        ]);

        $favoriteSiteId = $request->input('favorite_site_id');
        if ($favoriteSiteId !== 'other') {
            $request->validate([
                'favorite_site_id' => ['exists:sites,id'],
            ]);
        } else {
            $favoriteSiteId = null;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'favorite_site_id' => $favoriteSiteId,
            'password' => Hash::make($request->input('password')),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
