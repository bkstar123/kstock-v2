<?php

namespace Tests\Feature;

use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(string $password = 'secret123'): Admin
    {
        // refresh() so DB-default columns (e.g. status) are loaded onto the
        // in-memory model used by actingAs(); the bkscms-auth middleware
        // requires status == Admin::ACTIVE.
        return Admin::create([
            'name'     => 'Test Admin',
            'username' => 'tester',
            'email'    => 'tester@example.com',
            'password' => bcrypt($password),
        ])->refresh();
    }

    public function test_login_screen_renders()
    {
        $this->get('/cms/admins/login')
            ->assertStatus(200)
            ->assertSee('csrf-token', false);
    }

    public function test_guest_is_redirected_from_a_protected_route()
    {
        $this->get('/cms/financial-statements')->assertRedirect();
        $this->assertGuest('admins');
    }

    public function test_admin_can_log_in_with_valid_credentials()
    {
        $admin = $this->makeAdmin();

        $response = $this->post('/cms/admins/login', [
            'user'     => 'tester@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin, 'admins');
    }

    public function test_admin_cannot_log_in_with_wrong_password()
    {
        $this->makeAdmin();

        $this->post('/cms/admins/login', [
            'user'     => 'tester@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest('admins');
    }

    public function test_authenticated_admin_can_view_financial_statements_index()
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admins')
            ->get('/cms/financial-statements')
            ->assertStatus(200);
    }
}
