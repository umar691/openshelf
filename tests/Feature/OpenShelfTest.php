<?php

namespace Tests\Feature;

use App\Livewire\BookCatalog;
use App\Models\Book;
use App\Models\Content;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpenShelfTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_the_public_learning_library(): void
    {
        $author = User::factory()->create();
        Content::create([
            'user_id' => $author->id,
            'type' => 'resource',
            'title' => 'A guide to algebra',
            'body' => 'Start by isolating the unknown.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('A guide to algebra')
            ->assertSee('Study library');
    }

    public function test_a_signed_in_member_can_view_the_community_and_message_directory(): void
    {
        $member = User::factory()->create();
        User::factory()->create(['name' => 'Another learner']);

        $this->actingAs($member)
            ->get('/')
            ->assertOk()
            ->assertSee('Another learner')
            ->assertSee('Your account');
    }

    public function test_a_member_can_publish_work_and_save_or_appreciate_it(): void
    {
        $author = User::factory()->create();
        $this->actingAs($author)
            ->post('/content', [
                'type' => 'novel',
                'title' => 'A story in progress',
                'summary' => 'A new beginning',
                'body' => 'The first line of the story.',
                'topic' => 'Fiction',
                'tags' => 'short story, fiction',
                'status' => 'published',
            ])
            ->assertRedirect('/');

        $content = Content::where('title', 'A story in progress')->firstOrFail();
        $this->assertSame(['short story', 'fiction'], $content->tags);

        $this->postJson("/content/{$content->id}/bookmark")
            ->assertOk()
            ->assertJson(['saved' => true]);
        $this->postJson("/content/{$content->id}/reaction")
            ->assertOk()
            ->assertJson(['reacted' => true, 'count' => 1]);
    }

    public function test_drafts_are_private_and_only_their_author_can_publish_them(): void
    {
        $author = User::factory()->create();
        $otherMember = User::factory()->create();

        $this->actingAs($author)
            ->post('/content', [
                'type' => 'research',
                'title' => 'Private research draft',
                'body' => 'Notes not ready for the library.',
                'status' => 'draft',
            ])
            ->assertRedirect('/');

        $draft = Content::where('title', 'Private research draft')->firstOrFail();
        $this->assertSame('draft', $draft->status);

        $this->actingAs($otherMember)
            ->get('/')
            ->assertDontSee('Private research draft');
        $this->actingAs($author)
            ->get('/')
            ->assertSee('Private research draft')
            ->assertSee('My drafts');

        $this->postJson("/content/{$draft->id}/publish")
            ->assertOk()
            ->assertJson(['published' => true]);
        $this->assertDatabaseHas('contents', ['id' => $draft->id, 'status' => 'published']);

        $this->actingAs($otherMember)
            ->postJson("/content/{$draft->id}/publish")
            ->assertForbidden();
    }

    public function test_vendor_can_publish_a_book_using_a_role_scoped_sanctum_token(): void
    {
        $vendor = User::factory()->create();
        $vendor->assignRole(Role::findOrCreate('vendor', 'web'));
        $token = $vendor->createToken('vendor integration', ['read', 'write'])->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/books', [
            'title' => 'A field guide to stars',
            'description' => 'An introduction to the night sky.',
            'formats' => ['epub', 'pdf'],
            'price_minor' => 1499,
            'currency' => 'PKR',
            'category' => 'Astronomy',
            'digital_available' => true,
            'physical_available' => false,
        ])->assertCreated()->assertJsonPath('data.status', 'draft');

        $bookId = $response->json('data.id');
        $this->withToken($token)
            ->postJson("/api/v1/books/{$bookId}/publish")
            ->assertOk()
            ->assertJsonPath('published', true);

        $this->assertDatabaseHas('books', ['id' => $bookId, 'vendor_id' => $vendor->id, 'status' => 'published']);
    }

    public function test_checkout_reprices_cart_and_reserves_physical_stock_transactionally(): void
    {
        $buyer = User::factory()->create();
        $buyer->assignRole(Role::findOrCreate('reader', 'web'));
        $token = $buyer->createToken('reader integration', ['read', 'write'])->plainTextToken;
        $physicalBook = Book::create([
            'vendor_id' => User::factory()->create()->id,
            'title' => 'Printed astronomy atlas',
            'slug' => 'printed-astronomy-atlas',
            'description' => 'A printed star atlas.',
            'formats' => ['print'],
            'price_minor' => 2500,
            'currency' => 'PKR',
            'category' => 'Astronomy',
            'status' => 'published',
            'digital_available' => false,
            'physical_available' => true,
            'stock_quantity' => 2,
            'published_at' => now(),
        ]);

        $this->withToken($token)->postJson("/api/v1/cart/books/{$physicalBook->id}", [
            'quantity' => 2,
            'format' => 'print',
            'fulfillment' => 'physical',
        ])->assertCreated();

        $this->withToken($token)->postJson('/api/v1/orders', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipping_address');
        $this->assertDatabaseHas('books', ['id' => $physicalBook->id, 'stock_quantity' => 2]);

        $response = $this->withToken($token)->postJson('/api/v1/orders', [
            'shipping_address' => [
                'name' => 'Reader',
                'line1' => '1 Library Road',
                'city' => 'Lahore',
                'country' => 'PK',
            ],
        ])->assertCreated()->assertJsonPath('data.total_minor', 5000);

        $this->assertDatabaseHas('books', ['id' => $physicalBook->id, 'stock_quantity' => 0]);
        $this->assertDatabaseHas('stock_reservations', [
            'order_item_id' => $response->json('data.items.0.id'),
            'status' => 'reserved',
        ]);
    }

    public function test_book_catalog_is_a_livewire_page(): void
    {
        $this->get('/catalog')->assertOk()->assertSee('Find your next');
    }

    public function test_catalog_adds_books_to_the_signed_in_users_cart(): void
    {
        $reader = User::factory()->create();
        $book = Book::create([
            'vendor_id' => User::factory()->create()->id,
            'title' => 'An open science reader',
            'slug' => 'an-open-science-reader',
            'description' => 'A digital reader.',
            'formats' => ['pdf'],
            'price_minor' => 1200,
            'currency' => 'PKR',
            'category' => 'Science',
            'status' => 'published',
            'digital_available' => true,
            'physical_available' => false,
            'published_at' => now(),
        ]);

        Livewire::test(BookCatalog::class)
            ->call('addToCart', $book->id)
            ->assertRedirect('/login');

        Livewire::actingAs($reader)
            ->test(BookCatalog::class)
            ->set("selectedFormats.{$book->id}", 'pdf')
            ->call('addToCart', $book->id)
            ->assertHasNoErrors()
            ->assertSee('An open science reader was added to your cart.');

        $this->assertDatabaseHas('cart_items', [
            'purchasable_type' => $book->getMorphClass(),
            'purchasable_id' => $book->id,
        ]);
    }

    public function test_purchasing_a_free_course_creates_an_enrollment_without_payment(): void
    {
        $student = User::factory()->create();
        $token = $student->createToken('course integration', ['read', 'write'])->plainTextToken;
        $course = Course::create([
            'instructor_id' => User::factory()->create()->id,
            'title' => 'Foundations of statistics',
            'slug' => 'foundations-of-statistics',
            'description' => 'A free introductory course.',
            'subject' => 'Statistics',
            'difficulty' => 'beginner',
            'status' => 'published',
            'price_minor' => 0,
            'currency' => 'PKR',
            'published_at' => now(),
        ]);

        $this->withToken($token)
            ->postJson("/api/v1/cart/courses/{$course->id}")
            ->assertCreated();

        $order = $this->withToken($token)
            ->postJson('/api/v1/orders', [])
            ->assertCreated()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.total_minor', 0);

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'active',
        ]);
        $this->assertSame(0, $order->json('data.total_minor'));
    }

    public function test_chatify_and_filament_are_integrated_with_authenticated_roles(): void
    {
        $member = User::factory()->create();
        $member->assignRole(Role::findOrCreate('reader', 'web'));
        $this->actingAs($member)->get('/chatify')->assertOk();

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_members_can_comment_and_send_direct_messages(): void
    {
        $author = User::factory()->create();
        $reader = User::factory()->create();
        $content = Content::create([
            'user_id' => $author->id,
            'type' => 'post',
            'title' => 'A community question',
            'body' => 'What are you learning?',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($reader)
            ->postJson("/api/content/{$content->id}/comments", ['body' => 'Thank you for sharing.'])
            ->assertCreated()
            ->assertJsonPath('body', 'Thank you for sharing.');

        $this->postJson("/api/chat/{$author->id}", ['body' => 'Would you like to study together?'])
            ->assertCreated()
            ->assertJsonPath('body', 'Would you like to study together?');
    }

    public function test_guests_can_browse_but_must_sign_in_to_publish(): void
    {
        $this->get('/')->assertOk();

        $this->post('/content', [
            'type' => 'post',
            'title' => 'A post',
            'body' => 'Body',
            'status' => 'published',
        ])->assertRedirect('/login');

        $this->get('/login')->assertRedirect('/');
    }

    public function test_a_visitor_can_create_an_account_and_start_a_session(): void
    {
        $this->post('/register', [
            'name' => 'New Reader',
            'email' => 'reader@example.test',
            'password' => 'Curious1234',
            'password_confirmation' => 'Curious1234',
        ])
            ->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'reader@example.test']);
    }
}
