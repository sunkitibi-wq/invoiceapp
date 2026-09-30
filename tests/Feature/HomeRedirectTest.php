<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_guests_to_the_login_page(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login'));
    }

    public function test_it_sends_authenticated_admins_to_the_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
