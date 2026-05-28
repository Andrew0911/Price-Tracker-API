<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class SocialAndMessageTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_users_can_send_and_accept_friend_requests(): void
    {
        $sender = $this->actingAsUser($this->user(['private_account' => false]));
        $receiver = $this->user(['private_account' => false]);

        $this->postJson("/api/friends/{$receiver->id}")
            ->assertOk();

        $this->assertDatabaseHas('friendships', [
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        $this->actingAsUser($receiver);

        $this->postJson("/api/friends/{$sender->id}/accept")
            ->assertOk();

        $this->assertDatabaseHas('friendships', [
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $receiver->id,
            'friend_id' => $sender->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
    }

    public function test_user_discovery_respects_privacy_and_friendship_statuses(): void
    {
        $authUser = $this->actingAsUser($this->user(['name' => 'Auth', 'private_account' => false]));
        $public = $this->user(['name' => 'Public User', 'email' => 'public@example.test', 'private_account' => false]);
        $private = $this->user(['name' => 'Private User', 'email' => 'private@example.test', 'private_account' => true]);
        $friend = $this->user(['name' => 'Friend User', 'email' => 'friend@example.test', 'private_account' => true]);
        $pendingReceived = $this->user(['name' => 'Pending User', 'email' => 'pending@example.test', 'private_account' => false]);
        $this->admin(['email' => 'admin-social@example.test']);

        $this->makeFriends($authUser, $friend);
        Friendship::create([
            'user_id' => $pendingReceived->id,
            'friend_id' => $authUser->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $public->id, 'friendship_status' => 'none'])
            ->assertJsonFragment(['id' => $friend->id, 'friendship_status' => 'accepted'])
            ->assertJsonFragment(['id' => $pendingReceived->id, 'friendship_status' => 'pending_received'])
            ->assertJsonMissing(['id' => $private->id]);

        $this->getJson('/api/users?search=private@example.test')
            ->assertOk()
            ->assertJsonFragment(['id' => $private->id, 'friendship_status' => 'none']);
    }

    public function test_friend_requests_can_be_declined_removed_and_not_duplicated(): void
    {
        $alice = $this->actingAsUser($this->user());
        $bob = $this->user();

        $this->postJson("/api/friends/{$alice->id}")
            ->assertUnprocessable();

        $this->postJson("/api/friends/{$bob->id}")
            ->assertOk();

        $this->postJson("/api/friends/{$bob->id}")
            ->assertConflict();

        $this->actingAsUser($bob);

        $this->getJson('/api/friends/requests')
            ->assertOk()
            ->assertJsonFragment(['id' => $alice->id]);

        $this->deleteJson("/api/friends/{$alice->id}/decline")
            ->assertOk();

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
        ]);

        $this->makeFriends($alice, $bob);

        $this->getJson('/api/friends')
            ->assertOk()
            ->assertJsonFragment(['id' => $alice->id]);

        $this->deleteJson("/api/friends/{$alice->id}")
            ->assertOk();

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
        ]);
        $this->assertDatabaseMissing('friendships', [
            'user_id' => $bob->id,
            'friend_id' => $alice->id,
        ]);
    }

    public function test_accept_requires_pending_request(): void
    {
        $alice = $this->actingAsUser($this->user());
        $bob = $this->user();

        $this->postJson("/api/friends/{$bob->id}/accept")
            ->assertNotFound();
    }

    public function test_admin_accounts_cannot_use_social_or_messaging_features(): void
    {
        $admin = $this->actingAsUser($this->admin());
        $user = $this->user();

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson("/api/friends/{$user->id}")->assertForbidden();
        $this->getJson('/api/messages/unread-counts')->assertForbidden();
        $this->postJson("/api/messages/{$user->id}", ['body' => 'Hello'])->assertForbidden();

        $this->assertTrue($admin->is_admin);
    }

    public function test_messages_require_friendship_and_mark_messages_as_read(): void
    {
        $alice = $this->actingAsUser($this->user());
        $bob = $this->user();

        $this->postJson("/api/messages/{$bob->id}", ['body' => 'Hello'])
            ->assertForbidden();

        $this->makeFriends($alice, $bob);

        $this->postJson("/api/messages/{$bob->id}", ['body' => 'Hello'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Hello')
            ->assertJsonPath('data.is_mine', true);

        $message = Message::create([
            'sender_id' => $bob->id,
            'receiver_id' => $alice->id,
            'body' => 'Unread',
        ]);

        $this->getJson("/api/messages/{$bob->id}")
            ->assertOk();

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_chat_unread_counts_and_product_sharing_rules(): void
    {
        $alice = $this->actingAsUser($this->user());
        $bob = $this->user();
        $charlie = $this->user();
        $ownedProduct = $this->product(['name' => 'Shared product']);
        $notOwnedProduct = $this->product(['name' => 'Someone elses product']);

        $this->attachProduct($alice, $ownedProduct);
        $this->makeFriends($alice, $bob);
        $this->makeFriends($alice, $charlie);

        Message::create([
            'sender_id' => $bob->id,
            'receiver_id' => $alice->id,
            'body' => 'Unread from Bob',
        ]);
        Message::create([
            'sender_id' => $charlie->id,
            'receiver_id' => $alice->id,
            'body' => 'Unread from Charlie',
        ]);

        $this->getJson('/api/messages/unread-counts')
            ->assertOk()
            ->assertJsonPath("data.{$bob->id}", 1)
            ->assertJsonPath("data.{$charlie->id}", 1);

        $this->postJson("/api/messages/{$bob->id}", [
            'product_id' => $notOwnedProduct->id,
        ])->assertForbidden();

        $this->postJson("/api/messages/{$bob->id}", [
            'body' => '',
            'product_id' => $ownedProduct->id,
        ])->assertCreated()
            ->assertJsonPath('data.product.id', $ownedProduct->id);
    }
}
