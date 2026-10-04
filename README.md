# OpenShelf

OpenShelf is a modular Laravel marketplace and learning-community foundation: member-published books, courses, jobs, carts/orders, applications, milestone-based contract records, role-scoped APIs, and Chatify conversations/groups.

> This existing application is Laravel 12, not Laravel 11. It was kept on Laravel 12 instead of risking a framework downgrade. The selected current package majors are compatible with this app.

## Stack

- Laravel 12.69
- Filament 4.14 admin panel (`/admin`)
- Livewire 3 book catalog (`/catalog`)
- Sanctum 4 API tokens and session authentication
- Laravel Socialite sign-in with Google, Microsoft, TikTok, and LinkedIn, linked to existing accounts from the profile panel
- Chatify 2.0 beta (`/chatify`, direct and group conversations, attachments, presence, typing/read events)
- Laravel Reverb 1 WebSocket server
- Spatie Laravel Permission 6 polymorphic multiple roles
- Stripe PHP SDK 21 Checkout adapter
- MySQL 8+ recommended for production; SQLite is configured for isolated feature tests
- Blade/CSS/vanilla JS frontend; Chatify's built assets mean no Node build is needed to run the existing interface

The implementation map, database design, routes/controllers/services, and payment integration guide are in [the platform blueprint](./docs/PLATFORM_BLUEPRINT.md).

## Local setup

Requirements: PHP 8.2+ with `fileinfo`, `intl`, and `pdo_mysql`, Composer 2+, and a reachable MySQL 8+ or MariaDB server.

1. Create an `openshelf` MySQL database using `utf8mb4`. Create a dedicated application account; do not use MySQL's root account in production.
2. Configure `.env`: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.
3. Install and initialize:

   ```powershell
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   ```

4. Visit `http://127.0.0.1:8000`. The Filament administrator dashboard is at `/admin`; the Chatify messenger is at `/chatify`; the Livewire book catalog is at `/catalog`.

This development machine has no MySQL server running. Its PHP extensions are installed but disabled in the global `php.ini`; one-process setup commands can enable them without changing machine configuration:

```powershell
php -d extension=php_fileinfo.dll -d extension=php_intl.dll -d extension=php_pdo_mysql.dll artisan migrate --seed
php -d extension=php_fileinfo.dll -d extension=php_intl.dll -d extension=php_pdo_mysql.dll artisan storage:link
php -d extension=php_fileinfo.dll -d extension=php_intl.dll -d extension=php_pdo_mysql.dll -S 127.0.0.1:8000 -t public
```

Run those only after starting MySQL and setting valid `.env` credentials. The automated feature tests use their own in-memory SQLite database and do not depend on a local MySQL server.

## Sign-in providers

Email/password registration and login work without an OAuth provider. Google, Microsoft, TikTok, and LinkedIn sign-in are wired through Laravel Socialite; add each provider's client ID/key and secret to `.env` to enable it. Their callback URLs must exactly match the application URL:

```text
https://your-domain.example/auth/google/callback
https://your-domain.example/auth/microsoft/callback
https://your-domain.example/auth/tiktok/callback
https://your-domain.example/auth/linkedin/callback
```

Configure those callback URLs in each provider's developer application and then set `APP_URL`, each provider's `*_CLIENT_ID` / `*_CLIENT_SECRET` and `*_REDIRECT_URI` in `.env`. TikTok calls its OAuth client ID a **Client key** in the developer portal; enter that value as `TIKTOK_CLIENT_ID`. `MICROSOFT_TENANT=common` permits personal Microsoft accounts and work/school accounts; use `organizations`, `consumers`, or a tenant ID to narrow access. TikTok's Login Kit must be approved and include `user.info.basic`. The TikTok provider does not return email, so new TikTok users enter an email address after authorization; this address remains unverified until a separate verification flow is implemented. LinkedIn sign-in uses OpenID Connect (`openid profile email`) and requires the corresponding Sign In with LinkedIn product/access to be enabled for your LinkedIn app. The environment template contains blank credentials, so OAuth buttons explain setup is incomplete until credentials are supplied.

Run `php artisan migrate` to create the `social_accounts` table. Provider identities are stored separately from passwords and provider access tokens are not persisted. A verified Google identity can mark a new account email as verified. Microsoft-created and TikTok-created accounts are not marked email-verified. An existing email is never silently linked to a new external identity: sign into the existing account, then use **Your account → Connected sign-in methods** to connect a provider. OAuth callback state is checked by Socialite; use HTTPS and keep client secrets out of source control.

## Local development credentials

`php artisan db:seed` creates `hello@openshelf.test` / `openshelf123` for local development and assigns the local admin role so the Filament panel can be explored. Remove or rotate this demo account before deployment.

## Real-time chat

Chatify's complete UI, migrations, configuration, broadcasts, and static assets are installed. Configure unique `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` values (the checked-in `.env.example` contains no credentials), then run:

```powershell
php artisan reverb:start
php artisan queue:work
php artisan schedule:work
```

Use a process manager and TLS-terminating reverse proxy for deployment. Chatify's browser UI uses session authentication by default (`CHATIFY_API_MIDDLEWARE=web,auth`). For API clients, issue a Sanctum token with the least privileges required; do not put bearer tokens in browser storage for first-party SPAs.

## Payments

The Stripe Checkout adapter and verified/idempotent webhook processing are scaffolded. Easypaisa and JazzCash merchant credentials are configurable, but their provider adapters are deliberately not fabricated: obtain the correct merchant API/signature specification and sandbox credentials, then implement the `PaymentGateway` contract.

Escrow contracts, milestones, stock reservations, a payment journal, and payout records are domain foundations—not a regulated escrow/custody service. Do not claim or release client funds until legal, KYC/AML, reconciliation, chargeback, and provider settlement requirements are met.

## Tests and formatting

With the required PHP extensions available:

```powershell
vendor\bin\phpunit
vendor\bin\pint --test
```
