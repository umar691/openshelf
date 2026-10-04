# OpenShelf platform blueprint

This is the implementation map for the current repository. The repository remains on Laravel 12; the architecture is intentionally organized so a Laravel 11 deployment can be built separately with its own lockfile and compatibility verification.

## Directory structure

```text
app/
  Console/Commands/ReleaseExpiredReservations.php
  Filament/Resources/
    Books/                    # Books inventory/catalog moderation
    Courses/                  # Course moderation
    JobPosts/                 # Job post moderation
  Http/
    Controllers/
      Api/
        ApiTokenController.php
        BookController.php
        CartController.php
        CourseController.php
        JobController.php
        OrderController.php
        PaymentWebhookController.php
      AuthController.php
      SocialAuthController.php
      CommunityController.php
      ContentController.php
    Requests/
      AddBookToCartRequest.php
      ApplyToJobRequest.php
      CreateOrderRequest.php
      StartCheckoutRequest.php
      StoreBookRequest.php
      StoreCourseRequest.php
      StoreJobPostRequest.php
  Livewire/BookCatalog.php
  Models/
    Concerns/HasReviews.php
    Book.php, BookChapter.php, Review.php
    Cart.php, CartItem.php, Order.php, OrderItem.php
    Course.php, CourseModule.php, Lesson.php, LessonAttachment.php
    Quiz.php, QuizQuestion.php, LessonProgress.php, CourseEnrollment.php
    Gig.php, GigPackage.php, JobPost.php, JobApplication.php
    EscrowContract.php, ContractMilestone.php, PaymentTransaction.php
    PaymentWebhookEvent.php, StockReservation.php, DigitalEntitlement.php
    ContentReport.php, SocialAccount.php, User.php, Content.php, Comment.php, Message.php
  Policies/BookPolicy.php, CoursePolicy.php, JobPostPolicy.php, OrderPolicy.php
  Services/
    Auth/SocialAuthService.php
    Jobs/JobApplicationService.php, JobPostService.php
    Learning/CourseService.php
    Marketplace/BookService.php, CartService.php, InventoryService.php, OrderService.php
    Payments/Contracts/PaymentGateway.php
    Payments/Data/{CheckoutRequest,CheckoutSession,VerifiedWebhook}.php
    Payments/Gateways/StripePaymentGateway.php
    Payments/PaymentGatewayRegistry.php, PaymentOrchestrator.php
    Search/SearchService.php
database/
  migrations/                 # domain schemas and published package migrations
  seeders/DatabaseSeeder.php
routes/
  api.php, web.php, channels.php, chatify/channels.php
config/
  chatify.php, broadcasting.php, reverb.php, payments.php
resources/views/
  home.blade.php, layouts/app.blade.php, livewire/book-catalog.blade.php
  vendor/chatify/             # published package views
public/vendor/chatify/        # published package frontend assets
tests/Feature/OpenShelfTest.php, SocialAuthTest.php
```

## Modules and schema

All money is stored as integer minor units plus an ISO currency code. Status columns are strings so transitions can be extended without destructive enum changes. Production search uses MySQL full-text indexes for catalog titles/descriptions; SQLite tests use a `LIKE` fallback.

| Module | Tables | Notes |
|---|---|---|
| Identity and roles | `users`, `social_accounts`, `personal_access_tokens`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Email/password sessions and Socialite Google/Microsoft/TikTok/LinkedIn OAuth; provider IDs are unique and provider tokens are not stored. TikTok sign-up collects an email separately because Login Kit does not return it. Existing emails are not silently linked. Spatie polymorphic `model_has_roles` supports multiple roles per user. Roles: `reader`, `author`, `student`, `instructor`, `freelancer`, `employer`, `vendor`, `moderator`, `admin`. |
| Novels and store | `books`, `book_chapters`, `reviews`, `carts`, `cart_items`, `orders`, `order_items`, `stock_reservations`, `digital_entitlements` | `formats` is JSON; `reviews.reviewable_*` and cart/order item `purchasable_*` are polymorphic. `order_items` snapshot item title, unit price, currency, and fulfillment format so later catalog edits do not rewrite receipts. |
| Learning | `courses`, `course_modules`, `lessons`, `lesson_attachments`, `quizzes`, `quiz_questions`, `quiz_attempts`, `lesson_progress`, `course_enrollments` | Markdown, video URL, private attachment disk/path, quiz options and answer key, submitted attempt snapshots, and unique per-user lesson progress. Keep `correct_answers` server-only; never serialize quiz models wholesale to a learner. |
| Jobs and freelance | `gigs`, `gig_packages`, `job_posts`, `job_applications`, `escrow_contracts`, `contract_milestones`, `contract_disputes` | Package tier is constrained by unique `(gig_id, tier)`; requirements and feature lists are JSON; applications are unique per job/applicant; contract amounts and milestone amounts use minor currency units. |
| Payments and settlement | `payment_transactions`, `payment_webhook_events`, `vendor_payouts`, `platform_ledger_entries` | Provider event IDs and idempotency keys are unique. Webhooks verify the provider signature and amount/currency, lock the transaction, and process atomically. Journal entries are append-oriented; reconciliation and payout review still need operational tooling. |
| Chat and social safety | Chatify `ch_conversations`, `ch_conversation_participants`, `ch_messages`, `ch_message_user_states`, `ch_favorites`, `ch_user_blocks`, `ch_user_settings`; plus `user_follows`, `content_reports`, Laravel `notifications`, and existing community tables | Chatify v2 supplies direct/group rooms, attachments, presence, typing, read state, blocks, saved messages, and realtime events. Do not create a second set of production chat-room tables. |

Package migrations are published into this application's migration directory. Apply these migrations to a fresh MySQL database with `php artisan migrate --seed`; schema tests run against SQLite. Never run a destructive fresh migration on a database containing production data.

## HTTP entry points and responsibilities

### Web

- `/` — community library
- `/catalog` — full-page Livewire catalog with URL-synchronized search, category filtering, and pagination
- `/auth/{google|microsoft|tiktok|linkedin}/redirect` and `/callback` — Socialite sign-in; authenticated users connect providers from their account panel
- `/chatify` — Chatify authenticated conversation UI
- `/admin` — Filament panel, limited by `User::canAccessPanel()` to `admin`

### API v1

- Public read: `GET /api/v1/books`, `/books/{book}`, `/courses`, `/courses/{course}`, `/jobs`
- Authenticated token management: `POST /api/v1/tokens`, `DELETE /api/v1/tokens/current`
- Write scope: `POST /api/v1/books`, `/courses`, `/jobs`, `/jobs/{job}/applications`; publish endpoints on each catalog entity
- Commerce: `/api/v1/cart`, add/remove book or course items, create/list/show order, create checkout session. The Livewire book catalog adds selected editions to a signed-in user's cart; the current checkout boundary is the authenticated API.
- Verified provider callback: `POST /api/v1/payments/{gateway}/webhook`
- Chatify API: `/api/chatify/v1/...`, protected by the configured `web,auth` middleware by default. For a token-auth deployment, set `CHATIFY_WEB_ENABLED=false` and `CHATIFY_API_MIDDLEWARE=api,auth:sanctum`.

Controllers adapt requests and responses. Form Requests own validation/authorization. Services own slugs, job-application constraints, repricing, stock locks, checkout, and payment/webhook transitions. Policies authorize record access. Domain writes use database transactions, row locks, uniqueness constraints, and idempotency keys.

## MySQL and search

Set `DB_CONNECTION=mysql`, host/port, an existing database name, and a least-privilege database username/password in `.env`. The migrations add composite lookup indexes and MySQL full-text indexes to books, courses, and job posts. Use MySQL 8/InnoDB, `utf8mb4`, TLS in production, backups, and a connection pooler appropriate to the hosting environment. SQLite is only the isolated test driver.

## Sanctum and polymorphic roles

`User` uses `HasApiTokens`, Spatie `HasRoles`, and Chatify's `InteractsWithChatify`. Registration assigns `reader`; privileged workflows require the relevant role. Token issuance gives readers only `read`, and users with authoring/business roles `read` and `write`. Routes enforce `auth:sanctum` plus Sanctum's ability middleware aliases registered in `bootstrap/app.php`.

Role assignment is intentionally not self-service for `admin` or `moderator`. Add a verified onboarding workflow before granting high-trust vendor/employer privileges. Use policies for row ownership even when the user has the right broad role.

## Checkout, stock, escrow, and webhooks

Cart prices are not trusted: order creation reloads each published product, locks product rows, checks selected format/currency/stock, snapshots the current price, decrements physical stock inside the order transaction, and creates a 30-minute reservation. A zero-total order is fulfilled immediately, enrolling the buyer in free courses and granting any digital book entitlements without opening a payment session. The scheduled `marketplace:release-expired-reservations` command restores stock for unpaid expired orders. Run Laravel's scheduler in production.

Stripe checkout records one pending transaction per payable. Its webhook verifies `Stripe-Signature`, event ID idempotency, provider session reference, minor-unit amount, and currency before marking a transaction paid. Successful digital orders grant entitlements/course enrollment. Late successful payments after an order expires are marked `paid_review`; they are not automatically fulfilled. Staff must reconcile and refund or restore fulfillment.

Escrow contracts, disputes, milestones, and a platform ledger are schema foundations, not a fully licensed escrow operation. There is no milestone release/payout controller yet. Before releasing funds implement dual-party approval, an immutable balanced ledger, dispute/appeal policy, audited staff actions, provider payout reconciliation, KYC/AML/sanctions checks, chargeback/refund handling, and legal review for each operating jurisdiction.

### Stripe

The included `StripePaymentGateway` uses Stripe Checkout and signed webhooks. Set `STRIPE_SECRET` and `STRIPE_WEBHOOK_SECRET` in `.env`, configure the provider's webhook to `/api/v1/payments/stripe/webhook`, and test signature failures, duplicate delivery, wrong currency/amount, failure, expiry, refund, and delayed success with Stripe's sandbox. A browser return URL is not proof of payment.

### Easypaisa and JazzCash (Pakistan)

There is a `PaymentGateway` contract and per-gateway config slot, but no pretend adapter: Easypaisa/JazzCash merchant APIs and signatures vary by merchant/product. Obtain the current merchant integration pack and sandbox credentials directly from your merchant account. Then:

1. Implement `PaymentGateway::createCheckout()` and `verifyWebhook()` in a separate provider adapter using the documented sandbox endpoints and TLS requirements.
2. Validate the provider's exact canonical signing string/HMAC/hash algorithm; compare signatures in constant time. Never log secrets, OTP/PINs, card data, or full payment payloads.
3. Normalize success into `VerifiedWebhook` with the provider event ID, checkout reference, exact minor-unit amount, and ISO currency. Reject unsigned, replayed, mismatched, or out-of-order callbacks.
4. Register the adapter class in `config/payments.php` via `EASYPAISA_GATEWAY_DRIVER` / `JAZZCASH_GATEWAY_DRIVER`, set merchant values in `.env`, and test against provider sandbox before enabling production traffic.
5. Reconcile callbacks against settlement reports; do not infer a paid order from redirect pages or client JavaScript.

Stripe acceptance for Pakistani entities/currencies depends on the merchant's country/account and supported presentment/settlement currencies; confirm this with Stripe. Consider a local provider for PKR and keep provider-specific webhook adapters separate.

## Realtime and operations

Laravel Reverb is installed and broadcast channels are wired to Chatify's participant authorization. Provide unique Reverb credentials in `.env`, set `BROADCAST_CONNECTION=reverb` and `CHATIFY_BROADCAST_DRIVER=reverb`, then run `php artisan reverb:start`, queue workers, and `php artisan schedule:work` locally. Production should supervise these processes and put Reverb behind a TLS WebSocket proxy.

Large-scale rollout also needs object storage for book files/course attachments, queued media scanning, backups, moderation tools, audit retention, email verification/password reset delivery, rate limits, observability, CDN/private signed downloads, privacy/data-retention rules, abuse prevention, and load testing. Keep original upload files private and authorize every download against purchases/enrollment.
