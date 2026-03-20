<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_route_returns_view(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_adm_route_requires_authentication(): void
    {
        $response = $this->get('/adm');
        $response->assertRedirect('/login');
    }

    public function test_adm_dashboard_accessible_when_authenticated(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get('/adm');
        $response->assertStatus(200);
    }
}
