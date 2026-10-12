<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SharedExpensesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_group_and_adds_owner_as_member(): void
    {
        $owner = User::factory()->create(['email' => 'z@example.test']);
        Sanctum::actingAs($owner);
        $group = $this->postJson('/api/v1/groups', ['name' => 'Trip', 'currency' => 'RUB'])->assertCreated()->json();
        $this->assertDatabaseHas('group_members', ['group_id' => $group['id'], 'user_id' => $owner->id]);
    }

    public function test_creates_expense_with_authenticated_user_as_creator(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $group = $this->postJson('/api/v1/groups', ['name' => 'Trip', 'currency' => 'RUB'])->assertCreated()->json();

        $this->postJson("/api/v1/groups/{$group['id']}/expenses", [
            'payer_id' => $owner->id,
            'amount_cents' => 100000,
            'spent_on' => '2026-10-12',
            'category' => 'Food',
            'split_type' => 'equal',
            'shares' => [['user_id' => $owner->id]],
        ])->assertCreated();

        $this->assertDatabaseHas('expenses', ['group_id' => $group['id'], 'created_by_id' => $owner->id, 'amount_cents' => 100000]);
        $this->assertDatabaseHas('expense_shares', ['user_id' => $owner->id, 'amount_cents' => 100000]);
    }
}
