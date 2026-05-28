<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_must_verify_before_login(): void
    {
        $payload = [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ];

        $this->postJson('/api/register', $payload)
            ->assertOk()
            ->assertJsonPath('message', __('general.registrationSuccessful'));

        $this->assertDatabaseHas('users', [
            'email' => 'ada@example.test',
            'email_verified_at' => null,
        ]);

        $this->postJson('/api/login', [
            'email' => 'ada@example.test',
            'password' => 'Password1!',
        ])->assertUnauthorized();
    }

    public function test_verified_user_can_login_and_fetch_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.test',
            'password' => 'Password1!',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'verified@example.test',
            'password' => 'Password1!',
        ])->assertOk()
            ->assertJsonStructure(['data' => ['token', 'tokenType', 'expiresAt']]);

        $this->withToken($login->json('data.token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_email_verification_success_invalid_and_expired(): void
    {
        $user = User::factory()->create([
            'email' => 'verify@example.test',
            'email_verified_at' => null,
            'email_verification_token' => 'valid-token',
            'email_verification_token_expires_at' => now()->addHour(),
        ]);

        $this->postJson('/api/verify-email', [
            'email' => 'verify@example.test',
            'token' => 'wrong-token',
        ])->assertUnprocessable();

        $expired = User::factory()->create([
            'email' => 'expired@example.test',
            'email_verified_at' => null,
            'email_verification_token' => 'expired-token',
            'email_verification_token_expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/verify-email', [
            'email' => 'expired@example.test',
            'token' => 'expired-token',
        ])->assertUnprocessable();

        $this->postJson('/api/verify-email', [
            'email' => 'verify@example.test',
            'token' => 'valid-token',
        ])->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->email_verification_token);
        $this->assertNull($user->fresh()->email_verification_token_expires_at);
        $this->assertNull($expired->fresh()->email_verified_at);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'invalid-login@example.test',
            'password' => 'Password1!',
        ]);

        $this->postJson('/api/login', [
            'email' => 'invalid-login@example.test',
            'password' => 'WrongPass1!',
        ])->assertUnauthorized();
    }

    public function test_user_can_update_account_settings_but_admin_cannot(): void
    {
        $user = User::factory()->create(['private_account' => false]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/user', ['private_account' => true])
            ->assertOk()
            ->assertJsonPath('data.private_account', true);

        $this->assertTrue($user->fresh()->private_account);

        $admin = User::factory()->create(['is_admin' => true, 'private_account' => true]);
        Sanctum::actingAs($admin);

        $this->patchJson('/api/user', ['private_account' => false])
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->private_account);
    }

    public function test_user_can_logout_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.test',
            'password' => 'Password1!',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'logout@example.test',
            'password' => 'Password1!',
        ])->assertOk();

        $token = $login->json('data.token');

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_user_can_delete_account_with_valid_password(): void
    {
        $user = User::factory()->create([
            'password' => 'Password1!',
        ]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/account', ['password' => 'wrong-password'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->deleteJson('/api/account', ['password' => 'Password1!'])
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_forgot_password_validate_and_reset_flow(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.test',
            'password' => 'OldPass1!',
        ]);

        $this->postJson('/api/forgot-password', ['email' => 'reset@example.test'])
            ->assertOk();

        Mail::assertQueued(\App\Mail\ResetPasswordEmail::class);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password/validate', [
            'email' => 'reset@example.test',
            'token' => $token,
        ])->assertOk();

        $this->postJson('/api/reset-password', [
            'email' => 'reset@example.test',
            'token' => $token,
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPass1!', $user->fresh()->password));

        $this->postJson('/api/reset-password/validate', [
            'email' => 'reset@example.test',
            'token' => $token,
        ])->assertUnprocessable();
    }
}
