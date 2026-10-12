<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
            'receipt_url' => 'http://localhost:8000/storage/receipts/receipt.jpg',
            'split_type' => 'equal',
            'shares' => [['user_id' => $owner->id]],
        ])->assertCreated();

        $this->assertDatabaseHas('expenses', ['group_id' => $group['id'], 'created_by_id' => $owner->id, 'amount_cents' => 100000, 'receipt_path' => 'http://localhost:8000/storage/receipts/receipt.jpg']);
        $this->assertDatabaseHas('expense_shares', ['user_id' => $owner->id, 'amount_cents' => 100000]);
    }

    public function test_returns_frontend_invitation_link(): void
    {
        Mail::fake();
        config(['app.frontend_url' => 'http://localhost:5173']);
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $group = $this->postJson('/api/v1/groups', ['name' => 'Trip', 'currency' => 'RUB'])->assertCreated()->json();

        $response = $this->postJson("/api/v1/groups/{$group['id']}/invitations", ['email' => 'guest@example.test'])->assertCreated();

        $this->assertStringStartsWith('http://localhost:5173/invitations/', $response->json('accept_url'));
    }

    public function test_uploads_receipt_and_returns_public_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/receipts', ['receipt' => UploadedFile::fake()->image('receipt.jpg')])->assertCreated();

        $this->assertStringContainsString('/storage/receipts/', $response->json('url'));
        Storage::disk('public')->assertExists('receipts/'.basename(parse_url($response->json('url'), PHP_URL_PATH)));
    }
}
