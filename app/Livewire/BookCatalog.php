<?php

namespace App\Livewire;

use App\Models\Book;
use App\Services\Marketplace\CartService;
use App\Services\Search\SearchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class BookCatalog extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    public array $selectedFormats = [];

    public ?string $cartMessage = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function addToCart(int $bookId, CartService $carts): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect('/login', navigate: false);

            return;
        }

        $book = Book::query()->where('status', 'published')->findOrFail($bookId);
        $format = $this->selectedFormats[$bookId] ?? null;
        if (! is_string($format) || ! in_array($format, $book->formats ?? [], true)) {
            $this->addError('selectedFormats.'.$bookId, 'Choose an available edition first.');

            return;
        }

        $fulfillment = $format === 'print' ? 'physical' : 'digital';
        $carts->addBook($user, $book, 1, $format, $fulfillment);
        $this->cartMessage = "{$book->title} was added to your cart.";
    }

    public function render(SearchService $search): View
    {
        $books = $search->apply(
            Book::query()->where('status', 'published')->with('vendor:id,name'),
            $this->search,
        )
            ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
            ->latest('published_at')
            ->paginate(18);

        return view('livewire.book-catalog', [
            'books' => $books,
            'categories' => Book::query()->where('status', 'published')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }
}
