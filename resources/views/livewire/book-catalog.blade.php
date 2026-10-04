<section class="catalog">
    <div class="catalog-heading">
        <div class="eyebrow"><span class="eyebrow-line"></span> INDEPENDENT AUTHORS, ONE SHARED SHELF</div>
        <h1>Find your next <em>favorite.</em></h1>
        <p>Explore community-published books, from digital editions to print.</p>
    </div>
    <div class="catalog-tools">
        <label class="searchbox"><span>⌕</span><input type="search" wire:model.live.debounce.350ms="search" placeholder="Search books and authors..." aria-label="Search books"></label>
        <select wire:model.live="category" aria-label="Filter by category">
            <option value="">Every subject</option>
            @foreach ($categories as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach
        </select>
    </div>
    <div class="content-grid catalog-grid" wire:loading.class="is-loading" wire:target="search,category">
        @forelse ($books as $book)
            <article class="content-card">
                <div class="card-art art-novel">
                    @if ($book->cover_image)<img src="{{ $book->cover_image }}" alt="Cover of {{ $book->title }}" loading="lazy">@else<span class="card-art-symbol">✳</span><span class="card-art-label">{{ $book->category }}</span>@endif
                    <span class="type-label">{{ implode(' · ', $book->formats ?? []) }}</span>
                </div>
                <div class="card-content">
                    <div class="card-meta">{{ $book->category }} · {{ $book->vendor->name }}</div>
                    <h3>{{ $book->title }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit($book->description, 130) }}</p>
                    <div class="card-author"><span>{{ $book->currency }} {{ number_format($book->price_minor / 100, 2) }}</span></div>
                    <div class="catalog-buy">
                        <select wire:model="selectedFormats.{{ $book->id }}" aria-label="Choose a format for {{ $book->title }}">
                            <option value="">Choose an edition</option>
                            @foreach ($book->formats ?? [] as $format)<option value="{{ $format }}">{{ strtoupper($format) }}</option>@endforeach
                        </select>
                        <button class="button button-dark" type="button" wire:click="addToCart({{ $book->id }})">Add to cart</button>
                    </div>
                    @error('selectedFormats.'.$book->id)<p class="field-hint">{{ $message }}</p>@enderror
                </div>
            </article>
        @empty
            <div class="empty-state"><span>✳</span><h3>No books match just yet.</h3><p>Try a different search or check back as authors add to the shelf.</p></div>
        @endforelse
    </div>
    @if ($cartMessage)<p class="cart-confirmation" role="status">{{ $cartMessage }} Checkout is available through the authenticated API.</p>@endif
    <div class="catalog-pagination">{{ $books->links() }}</div>
</section>
