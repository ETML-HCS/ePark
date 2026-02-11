<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureOnboarded;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class EnsureOnboardedMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private EnsureOnboarded $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureOnboarded();
    }

    private function makeRequest(string $routeName = 'dashboard'): Request
    {
        $request = Request::create('/dashboard');
        $request->setRouteResolver(function () use ($routeName) {
            $route = new \Illuminate\Routing\Route('GET', '/dashboard', []);
            $route->name($routeName);
            return $route;
        });

        return $request;
    }

    private function next(): \Closure
    {
        return function ($request) {
            return new Response('OK');
        };
    }

    public function test_unauthenticated_user_passes_through(): void
    {
        $request = $this->makeRequest();

        $response = $this->middleware->handle($request, $this->next());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_admin_always_passes(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'onboarded' => false]);
        $request = $this->makeRequest();
        $request->setUserResolver(fn() => $admin);

        $response = $this->middleware->handle($request, $this->next());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_onboarded_user_passes(): void
    {
        $user = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        $request = $this->makeRequest();
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle($request, $this->next());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_non_onboarded_user_redirected_to_onboarding(): void
    {
        $user = User::factory()->create([
            'role' => 'locataire',
            'onboarded' => false,
            'favorite_site_id' => null,
        ]);
        $request = $this->makeRequest();
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle($request, $this->next());
        $this->assertTrue($response->isRedirect(route('onboarding.index')));
    }

    public function test_user_with_favorite_site_gets_auto_onboarded(): void
    {
        $user = User::factory()->create([
            'role' => 'locataire',
            'onboarded' => false,
        ]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        $user->update(['favorite_site_id' => $site->id]);
        $user->refresh();

        $request = $this->makeRequest();
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle($request, $this->next());
        $this->assertEquals('OK', $response->getContent());

        $user->refresh();
        $this->assertTrue($user->onboarded);
    }

    public function test_onboarded_user_redirected_away_from_onboarding(): void
    {
        $user = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        $request = $this->makeRequest('onboarding.index');
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle($request, $this->next());
        $this->assertTrue($response->isRedirect(route('dashboard')));
    }
}
