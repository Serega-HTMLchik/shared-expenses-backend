<?php

namespace Tests\Feature;

use App\Events\SharedExpensesChanged;
use App\Events\UserNotificationsChanged;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;
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

    public function test_broadcasts_created_group_change_to_its_member(): void
    {
        Event::fake([SharedExpensesChanged::class]);
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $group = $this->postJson('/api/v1/groups', ['name' => 'Trip', 'currency' => 'RUB'])->assertCreated()->json();

        Event::assertDispatched(SharedExpensesChanged::class, fn (SharedExpensesChanged $event): bool => $event->group->id === $group['id']
            && $event->type === 'group.created');
    }

    public function test_broadcasts_when_a_database_notification_is_stored(): void
    {
        Event::fake([UserNotificationsChanged::class]);
        $user = User::factory()->create();

        event(new NotificationSent($user, new Notification, 'database'));

        Event::assertDispatched(UserNotificationsChanged::class, fn (UserNotificationsChanged $event): bool => $event->user->is($user));
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

    public function test_returns_amounts_owed_to_the_current_user(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        Sanctum::actingAs($owner);
        $group = $this->postJson('/api/v1/groups', ['name' => 'Trip', 'currency' => 'RUB'])->assertCreated()->json();
        $groupModel = Group::findOrFail($group['id']);
        $groupModel->members()->create(['user_id' => $member->id]);

        $this->postJson("/api/v1/groups/{$group['id']}/expenses", [
            'payer_id' => $owner->id,
            'amount_cents' => 10000,
            'spent_on' => '2026-10-13',
            'split_type' => 'equal',
            'shares' => [['user_id' => $owner->id], ['user_id' => $member->id]],
        ])->assertCreated();

        $this->getJson('/api/v1/receivables')
            ->assertOk()
            ->assertJsonPath('0.from_user_id', $member->id)
            ->assertJsonPath('0.to_user_id', $owner->id)
            ->assertJsonPath('0.amount_cents', 5000)
            ->assertJsonPath('0.currency', 'RUB');
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
