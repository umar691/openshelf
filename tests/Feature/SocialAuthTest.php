<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_offers_email_and_all_four_social_sign_in_options(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Email address')
            ->assertSee('Continue with Google')
            ->assertSee('Continue with Microsoft')
            ->assertSee('Continue with TikTok')
            ->assertSee('Continue with LinkedIn');
    }

    public function test_an_authenticated_member_can_connect_sign_in_providers_from_their_account(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Connect Google')
            ->assertSee('Connect Microsoft')
            ->assertSee('Connect TikTok')
            ->assertSee('Connect LinkedIn');
    }

    public function test_a_verified_google_identity_can_create_and_sign_into_an_account(): void
    {
        $this->mockProviderIdentity('google', $this->identity(
            'google-user-1',
            'reader@example.test',
            'New Reader',
            ['email_verified' => true],
        ));

        $this->withSession(['social_auth_intent' => ['mode' => 'sign-in', 'provider' => 'google']])
            ->get('/auth/google/callback')
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'reader@example.test']);
        $this->assertNotNull(User::query()->where('email', 'reader@example.test')->firstOrFail()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'google-user-1',
        ]);
    }

    public function test_an_existing_email_is_not_automatically_linked_during_social_sign_in(): void
    {
        User::factory()->create(['email' => 'reader@example.test']);
        $this->mockProviderIdentity('google', $this->identity(
            'google-user-2',
            'reader@example.test',
            'Reader',
            ['email_verified' => true],
        ));

        $this->withSession(['social_auth_intent' => ['mode' => 'sign-in', 'provider' => 'google']])
            ->get('/auth/google/callback')
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'An account already uses this email. Sign in with email first, then connect this provider from your account.');

        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_a_signed_in_user_can_connect_a_microsoft_identity(): void
    {
        $user = User::factory()->create();
        $this->mockProviderIdentity('microsoft', $this->identity(
            'microsoft-user-1',
            'reader@outlook.test',
            'Reader',
        ));

        $this->actingAs($user)
            ->withSession(['social_auth_intent' => ['mode' => 'connect', 'provider' => 'microsoft', 'user_id' => $user->id]])
            ->get('/auth/microsoft/callback')
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Microsoft is now connected to your account.');

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'microsoft',
            'provider_user_id' => 'microsoft-user-1',
        ]);
    }

    public function test_a_new_tiktok_user_can_finish_registration_with_an_email(): void
    {
        $this->mockProviderIdentity('tiktok', $this->identity('tiktok-user-1', null, 'TikTok Reader'));

        $this->withSession(['social_auth_intent' => ['mode' => 'sign-in', 'provider' => 'tiktok']])
            ->get('/auth/tiktok/callback')
            ->assertRedirect(route('social.complete-profile'))
            ->assertSessionHas('social_auth_pending_profile.provider', 'tiktok');

        $this->get(route('social.complete-profile'))
            ->assertOk()
            ->assertSee('This provider did not share an email address with OpenShelf');

        $this->post(route('social.complete-profile.store'), ['email' => 'tiktok-reader@example.test'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'tiktok-reader@example.test',
            'email_verified_at' => null,
        ]);
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'tiktok',
            'provider_user_id' => 'tiktok-user-1',
        ]);
    }

    public function test_an_existing_tiktok_account_can_sign_in_without_sharing_an_email(): void
    {
        $user = User::factory()->create();
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'tiktok',
            'provider_user_id' => 'tiktok-user-existing',
        ]);
        $this->mockProviderIdentity('tiktok', $this->identity('tiktok-user-existing', null, 'TikTok Reader'));

        $this->withSession(['social_auth_intent' => ['mode' => 'sign-in', 'provider' => 'tiktok']])
            ->get('/auth/tiktok/callback')
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_provider_redirect_explains_when_credentials_are_not_configured(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get('/auth/google/redirect')
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Google sign-in needs its client ID and secret in the environment configuration.');
    }

    public function test_oauth_callback_rejects_a_provider_that_does_not_match_the_started_flow(): void
    {
        $this->withSession(['social_auth_intent' => ['mode' => 'sign-in', 'provider' => 'google']])
            ->get('/auth/tiktok/callback')
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Your sign-in session expired. Please start again.');

        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_configured_microsoft_sign_in_redirects_to_the_microsoft_authorization_endpoint(): void
    {
        config([
            'services.microsoft.client_id' => 'test-client-id',
            'services.microsoft.client_secret' => 'test-client-secret',
            'services.microsoft.redirect' => 'http://localhost/auth/microsoft/callback',
            'services.microsoft.tenant' => 'common',
        ]);

        $response = $this->get('/auth/microsoft/redirect')->assertRedirect();

        $this->assertStringContainsString(
            'login.microsoftonline.com/common/oauth2/v2.0/authorize',
            (string) $response->headers->get('Location'),
        );
    }

    public function test_configured_tiktok_sign_in_redirects_to_login_kit(): void
    {
        config([
            'services.tiktok.client_id' => 'test-tiktok-key',
            'services.tiktok.client_secret' => 'test-tiktok-secret',
            'services.tiktok.redirect' => 'http://localhost/auth/tiktok/callback',
        ]);

        $response = $this->get('/auth/tiktok/redirect')->assertRedirect();
        $location = (string) $response->headers->get('Location');

        $this->assertStringContainsString('www.tiktok.com/v2/auth/authorize/', $location);
        $this->assertStringContainsString('client_key=test-tiktok-key', $location);
        $this->assertStringContainsString('user.info.basic', $location);
    }

    public function test_configured_linkedin_sign_in_uses_openid_connect_scopes(): void
    {
        config([
            'services.linkedin.client_id' => 'test-linkedin-id',
            'services.linkedin.client_secret' => 'test-linkedin-secret',
            'services.linkedin.redirect' => 'http://localhost/auth/linkedin/callback',
        ]);

        $response = $this->get('/auth/linkedin/redirect')->assertRedirect();
        $location = (string) $response->headers->get('Location');

        $this->assertStringContainsString('www.linkedin.com/oauth/v2/authorization', $location);
        $this->assertStringContainsString('openid', $location);
        $this->assertStringContainsString('email', $location);
    }

    public function test_provider_identity_cannot_be_linked_to_a_second_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        SocialAccount::create([
            'user_id' => $owner->id,
            'provider' => 'microsoft',
            'provider_user_id' => 'microsoft-user-owned',
        ]);
        $this->mockProviderIdentity('microsoft', $this->identity(
            'microsoft-user-owned',
            'reader@outlook.test',
            'Reader',
        ));

        $this->actingAs($otherUser)
            ->withSession(['social_auth_intent' => ['mode' => 'connect', 'provider' => 'microsoft', 'user_id' => $otherUser->id]])
            ->get('/auth/microsoft/callback')
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'That provider account is already connected to another OpenShelf account.');

        $this->assertDatabaseCount('social_accounts', 1);
    }

    private function mockProviderIdentity(string $providerName, SocialiteUser $identity): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($identity);

        Socialite::shouldReceive('driver')
            ->once()
            ->with($providerName)
            ->andReturn($provider);
    }

    private function identity(string $id, ?string $email, string $name, array $raw = []): SocialiteUser
    {
        return (new SocialiteUser)
            ->setRaw($raw)
            ->map(['id' => $id, 'email' => $email, 'name' => $name]);
    }
}
