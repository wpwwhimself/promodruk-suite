<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_any_role_can_see_dashboard(): void
    {
        $users = Role::all()
            ->map(fn ($r) => $r->users->first());

        foreach ($users as $user) {
            $res = $this->actingAs($user)
                ->get(route("dashboard"));

            $res->assertStatus(200);
        }
    }

    public function test_non_authorized_user_cannot_access_page_not_meant_for_them(): void
    {
        $pairings = [
            ["Edytor", "settings"],
        ];

        foreach ($pairings as [$role, $route]) {
            $role = Role::find($role);
            $user = User::all()
                ->firstWhere(fn ($u) => $u->roles->containsOneItem($role));

            $res = $this->actingAs($user)
                ->get(route($route));

            $res->assertStatus(403);
        }
    }
}
