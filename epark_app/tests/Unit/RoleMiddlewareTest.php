<?php

namespace Tests\Unit;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private RoleMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RoleMiddleware();
    }

    private function makeRequest(?User $user = null): Request
    {
        $request = Request::create('/test');
        if ($user) {
            $request->setUserResolver(fn() => $user);
        }

        return $request;
    }

    private function next(): \Closure
    {
        return function ($request) {
            return new Response('OK');
        };
    }

    public function test_unauthenticated_user_gets_403(): void
    {
        $this->expectException(HttpException::class);

        $request = $this->makeRequest();
        $this->middleware->handle($request, $this->next(), 'admin');
    }

    public function test_user_with_matching_role_passes(): void
    {
        $user = User::factory()->create(['role' => 'proprietaire']);
        $request = $this->makeRequest($user);

        $response = $this->middleware->handle($request, $this->next(), 'proprietaire');
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_user_with_les_deux_always_passes(): void
    {
        $user = User::factory()->create(['role' => 'les deux']);
        $request = $this->makeRequest($user);

        $response = $this->middleware->handle($request, $this->next(), 'proprietaire');
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_user_with_wrong_role_gets_403(): void
    {
        $this->expectException(HttpException::class);

        $user = User::factory()->create(['role' => 'locataire']);
        $request = $this->makeRequest($user);

        $this->middleware->handle($request, $this->next(), 'proprietaire');
    }

    public function test_multiple_roles_accepted(): void
    {
        $user = User::factory()->create(['role' => 'locataire']);
        $request = $this->makeRequest($user);

        $response = $this->middleware->handle($request, $this->next(), 'locataire', 'proprietaire');
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_admin_role_must_be_explicit(): void
    {
        // Admin doesn't automatically pass — must be listed
        $this->expectException(HttpException::class);

        $user = User::factory()->create(['role' => 'admin']);
        $request = $this->makeRequest($user);

        $this->middleware->handle($request, $this->next(), 'proprietaire');
    }

    public function test_les_deux_bypasses_admin_restriction(): void
    {
        // Bug: "les deux" passes even for admin-only routes
        $user = User::factory()->create(['role' => 'les deux']);
        $request = $this->makeRequest($user);

        $response = $this->middleware->handle($request, $this->next(), 'admin');
        $this->assertEquals('OK', $response->getContent());
    }
}
