<?php

namespace App\Services\Auth;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Spatie\Permission\Models\Role;

class SocialAuthService
{
    public function signIn(string $provider, SocialiteUser $identity): User
    {
        $providerUserId = $this->providerUserId($identity);
        $user = $this->findLinkedUser($provider, $providerUserId);

        if ($user) {
            return $user;
        }

        $email = $this->email($identity);

        return $this->createAccount($provider, $providerUserId, $identity->getName(), $email, $this->emailIsVerified($provider, $identity));
    }

    public function findLinkedUser(string $provider, string $providerUserId): ?User
    {
        return SocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first()
            ?->user;
    }

    public function signInWithEmail(string $provider, string $providerUserId, ?string $name, string $email): User
    {
        $existing = $this->findLinkedUser($provider, $providerUserId);
        if ($existing) {
            return $existing;
        }

        return $this->createAccount($provider, $providerUserId, $name, mb_strtolower($email), false);
    }

    public function connect(User $user, string $provider, SocialiteUser $identity): void
    {
        $providerUserId = $this->providerUserId($identity);
        $existingOwner = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->value('user_id');

        if ($existingOwner !== null && (int) $existingOwner !== $user->id) {
            throw new SocialAuthException('That provider account is already connected to another OpenShelf account.');
        }

        DB::transaction(function () use ($user, $provider, $providerUserId): void {
            $user->socialAccounts()->updateOrCreate(
                ['provider' => $provider],
                ['provider_user_id' => $providerUserId],
            );
        });
    }

    private function providerUserId(SocialiteUser $identity): string
    {
        $id = $identity->getId();
        if (! is_string($id) && ! is_int($id)) {
            throw new SocialAuthException('The identity provider did not return a valid account identifier.');
        }

        return (string) $id;
    }

    private function email(SocialiteUser $identity): string
    {
        $email = $identity->getEmail();
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new SocialAuthException('The identity provider did not return a valid email address.');
        }

        return mb_strtolower($email);
    }

    private function emailIsVerified(string $provider, SocialiteUser $identity): bool
    {
        if (! in_array($provider, ['google', 'linkedin'], true)) {
            return false;
        }

        return filter_var(data_get($identity->getRaw(), 'email_verified'), FILTER_VALIDATE_BOOLEAN);
    }

    private function createAccount(string $provider, string $providerUserId, ?string $name, string $email, bool $emailIsVerified): User
    {
        if (User::query()->where('email', $email)->exists()) {
            throw new SocialAuthException('An account already uses this email. Sign in with email first, then connect this provider from your account.');
        }

        return DB::transaction(function () use ($provider, $providerUserId, $name, $email, $emailIsVerified): User {
            $user = User::create([
                'name' => Str::limit(trim((string) $name) ?: $email, 80, ''),
                'email' => $email,
                'password' => Str::random(64),
                'email_verified_at' => $emailIsVerified ? now() : null,
            ]);
            $user->assignRole(Role::findOrCreate('reader', 'web'));
            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
            ]);

            return $user;
        });
    }
}
