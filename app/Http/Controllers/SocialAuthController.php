<?php

namespace App\Http\Controllers;

use App\Exceptions\SocialAuthException;
use App\Services\Auth\SocialAuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const PROVIDER_LABELS = [
        'google' => 'Google',
        'microsoft' => 'Microsoft',
        'tiktok' => 'TikTok',
        'linkedin' => 'LinkedIn',
    ];

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        if (! $this->isConfigured($provider)) {
            return redirect()->route('home')->with('status', $this->providerLabel($provider).' sign-in needs its client ID and secret in the environment configuration.');
        }

        $request->session()->put('social_auth_intent', ['mode' => 'sign-in', 'provider' => $provider]);

        return Socialite::driver($provider)->redirect();
    }

    public function connect(Request $request, string $provider): RedirectResponse
    {
        if (! $this->isConfigured($provider)) {
            return redirect()->route('home')->with('status', $this->providerLabel($provider).' sign-in needs its client ID and secret in the environment configuration.');
        }

        $request->session()->put('social_auth_intent', [
            'mode' => 'connect',
            'provider' => $provider,
            'user_id' => $request->user()->id,
        ]);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider, SocialAuthService $socialAuth): RedirectResponse
    {
        if ($request->filled('error')) {
            $request->session()->forget('social_auth_intent');

            return redirect()->route('home')->with('status', 'Sign-in was cancelled or declined.');
        }

        $intent = $request->session()->pull('social_auth_intent');
        if (! is_array($intent) || ($intent['provider'] ?? null) !== $provider) {
            return redirect()->route('home')->with('status', 'Your sign-in session expired. Please start again.');
        }

        $identity = Socialite::driver($provider)->user();

        try {
            if (($intent['mode'] ?? null) === 'connect') {
                if (! $request->user() || (int) $request->user()->id !== (int) ($intent['user_id'] ?? 0)) {
                    return redirect()->route('home')->with('status', 'Your session changed during provider linking. Sign in and try connecting again.');
                }

                $socialAuth->connect($request->user(), $provider, $identity);

                return redirect()->route('home')->with('status', $this->providerLabel($provider).' is now connected to your account.');
            }

            $providerUserId = $identity->getId();
            if (is_string($providerUserId) || is_int($providerUserId)) {
                $linkedUser = $socialAuth->findLinkedUser($provider, (string) $providerUserId);
                if ($linkedUser) {
                    Auth::login($linkedUser);
                    $request->session()->regenerate();

                    return redirect()->intended(route('home'))->with('status', 'Welcome back to OpenShelf!');
                }
            }

            if (! filled($identity->getEmail()) && (is_string($providerUserId) || is_int($providerUserId))) {
                $request->session()->put('social_auth_pending_profile', [
                    'provider' => $provider,
                    'provider_user_id' => (string) $providerUserId,
                    'name' => $identity->getName(),
                ]);

                return redirect()->route('social.complete-profile');
            }

            Auth::login($socialAuth->signIn($provider, $identity));
            $request->session()->regenerate();

            return redirect()->intended(route('home'))->with('status', 'Welcome to OpenShelf!');
        } catch (SocialAuthException $exception) {
            return redirect()->route('home')->with('status', $exception->getMessage());
        }
    }

    public function completeProfile(Request $request): RedirectResponse|View
    {
        $provider = $request->session()->get('social_auth_pending_profile.provider');
        if (! is_string($provider) || ! array_key_exists($provider, self::PROVIDER_LABELS)) {
            return redirect()->route('home')->with('status', 'Start with a social sign-in to finish creating your account.');
        }

        return view('auth.complete-social-profile', ['providerLabel' => $this->providerLabel($provider)]);
    }

    public function storeProfile(Request $request, SocialAuthService $socialAuth): RedirectResponse
    {
        $pending = $request->session()->get('social_auth_pending_profile');
        if (! is_array($pending)
            || ! is_string($pending['provider'] ?? null)
            || ! array_key_exists($pending['provider'], self::PROVIDER_LABELS)
            || ! is_string($pending['provider_user_id'] ?? null)
        ) {
            return redirect()->route('home')->with('status', 'Your social sign-in expired. Please start again.');
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        try {
            $user = $socialAuth->signInWithEmail(
                $pending['provider'],
                $pending['provider_user_id'],
                is_string($pending['name'] ?? null) ? $pending['name'] : null,
                $data['email'],
            );
        } catch (SocialAuthException $exception) {
            return back()->withErrors(['email' => $exception->getMessage()])->withInput();
        }

        $request->session()->forget('social_auth_pending_profile');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('status', 'Welcome to OpenShelf! Your email is not verified yet.');
    }

    private function isConfigured(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }

    private function providerLabel(string $provider): string
    {
        return self::PROVIDER_LABELS[$provider] ?? $provider;
    }
}
