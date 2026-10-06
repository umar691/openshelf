<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Discover free books, publish articles, explore learning resources, and join welcoming study communities on OpenShelf.">
    <title>OpenShelf | Free Books, Learning and Study Communities</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="{{ asset('js/app.js') }}"></script>
    <meta name="robots" content="index, follow"> <link rel="canonical" href="{{ url('/') }}"> <meta name="theme-color" content="#f7f4ee"> <meta property="og:type" content="website"> <meta property="og:title" content="OpenShelf | Free Books, Learning and Study Communities"> <meta property="og:description" content="Discover free books, publish articles, explore learning resources, and join welcoming study communities on OpenShelf."> <meta property="og:site_name" content="OpenShelf"> <meta property="og:url" content="{{ url('/') }}"> <meta property="og:locale" content="en_US"> <meta name="twitter:card" content="summary"> <meta name="twitter:title" content="OpenShelf | Free Books, Learning and Study Communities"> <meta name="twitter:description" content="Discover free books, publish articles, explore learning resources, and join welcoming study communities on OpenShelf."> <script type="application/ld+json">{"@context":"https://schema.org","@type":"WebSite","name":"OpenShelf","url":"{{ url('/') }}","description":"Discover free books, publish articles, explore learning resources, and join welcoming study communities on OpenShelf."}</script> <script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization","name":"OpenShelf","url":"{{ url('/') }}"}</script> </head>
<body id="top">
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}" aria-label="OpenShelf home"><span class="brand-mark">o.</span><span>openshelf</span></a>
        <label class="searchbox"><span aria-hidden="true">⌕</span><input id="global-search" type="search" placeholder="Search stories, subjects, people..." aria-label="Search"></label>
        <div class="top-actions">
            @auth
                <a class="icon-button" href="{{ url('/chatify') }}" aria-label="Open Chatify messages">✉<span class="status-dot"></span></a>
                <button class="profile-chip" data-open-panel="profile"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span>{{ auth()->user()->name }}</span></button>
            @else
                <button class="icon-button" data-open-dialog="login-dialog" aria-label="Log in to messages">✉</button>
                <button class="button button-quiet" data-open-dialog="login-dialog">Log in</button>
                <button class="button button-dark" data-open-dialog="register-dialog">Join free <span>↗</span></button>
            @endauth
        </div>
    </header>

    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-label">YOUR SPACE</div>
            <nav class="side-nav" aria-label="Main navigation">
                <button class="nav-link is-active" data-view="home"><span>⌂</span> Home</button>
                <button class="nav-link" data-view="novel"><span>▤</span> Stories &amp; novels</button>
                <button class="nav-link" data-view="research"><span>⌘</span> Research papers</button>
                <button class="nav-link" data-view="resource"><span>▧</span> Study library</button>
                <a class="nav-link" href="{{ route('catalog') }}"><span>◈</span> Book marketplace</a>
                <button class="nav-link" data-view="post"><span>◉</span> Community</button>
                @auth<button class="nav-link" data-view="draft"><span>▣</span> My drafts</button>@endauth
                @auth<a class="nav-link" href="{{ url('/chatify') }}"><span>✉</span> Chatify messenger <span class="nav-count">LIVE</span></a>@else<button class="nav-link" data-open-panel="chat"><span>✉</span> Messages</button>@endauth
            </nav>
            <div class="sidebar-label topic-heading">EXPLORE TOPICS</div>
            <div class="topic-list">
                <button data-topic="Literature"><i class="topic-dot coral"></i> Literature</button>
                <button data-topic="Science"><i class="topic-dot violet"></i> Science</button>
                <button data-topic="History"><i class="topic-dot gold"></i> History</button>
                <button data-topic="Learning science"><i class="topic-dot teal"></i> Learning science</button>
                <button data-topic="Study skills"><i class="topic-dot blue"></i> Study skills</button>
            </div>
            <div class="sidebar-note"><span class="note-spark">✳</span><strong>Good things grow when shared.</strong><p>Your next favorite idea might be one click away.</p><button data-open-dialog="compose-dialog">Share something <span>↗</span></button></div>
            <div class="sidebar-footer">Made for the love of learning <span>✳</span></div>
        </aside>

        <main class="main-column">
            <div class="welcome-strip"><span>{{ now()->format('l, F j') }}</span><span class="strip-message">A little curiosity goes a long way. <b>✳</b></span></div>
            <section class="hero">
                <div class="hero-copy">
                    <div class="eyebrow"><span class="eyebrow-line"></span> YOUR COMMUNITY LIBRARY</div>
                    <h1>Big ideas.<br><em>Open minds.</em></h1>
                    <p>Discover stories, share what you know, and learn alongside people who are curious about the world.</p>
                    <div class="hero-actions"><button class="button button-dark" data-open-dialog="compose-dialog">Share your work <span>↗</span></button><button class="button button-outline" data-view="resource">Explore the library</button></div>
                    <div class="hero-social"><div class="avatar-stack"><span>J</span><span>M</span><span>A</span><span>+</span></div><span>Curiosity is better together</span></div>
                </div>
                <div class="hero-art" aria-label="Illustration of an open book and a growing plant">
                    <div class="art-sun"></div><div class="art-leaf leaf-one"></div><div class="art-leaf leaf-two"></div><div class="art-book"><div class="book-page page-left"></div><div class="book-page page-right"></div><div class="book-spine"></div></div><span class="art-word word-one">wonder</span><span class="art-word word-two">grow</span><span class="art-star">✳</span><div class="art-caption">Ideas to get lost in,<br>knowledge to carry home.</div>
                </div>
            </section>

            <section class="stats-row" aria-label="OpenShelf community statistics"><div><strong>{{ number_format($stats['members']) }}</strong><span>curious minds</span></div><div><strong>{{ number_format($stats['library']) }}</strong><span>things to discover</span></div><div><strong>{{ number_format($stats['research']) }}</strong><span>research notes</span></div><div class="stat-message"><span>✳</span> Yours could be next.</div></section>

            <section class="discover-section" id="discover">
                <div class="section-heading"><div><div class="eyebrow"><span class="eyebrow-line"></span> A LITTLE OF EVERYTHING</div><h2 id="feed-heading">Fresh from the community</h2></div><button class="text-button" data-open-dialog="compose-dialog">Share your work <span>↗</span></button></div>
                <div class="filter-row" role="tablist" aria-label="Filter library"><button class="filter-pill is-selected" data-filter="all">For you</button><button class="filter-pill" data-filter="novel">Stories</button><button class="filter-pill" data-filter="research">Research</button><button class="filter-pill" data-filter="resource">Study resources</button><button class="filter-pill" data-filter="post">Community</button></div>
                <div class="content-grid" id="content-grid">
                    @forelse ($contents as $content)
                        <article class="content-card" @if ($content->status === 'draft') hidden @endif data-status="{{ $content->status }}" data-type="{{ $content->type }}" data-topic="{{ e($content->topic ?? '') }}" data-search="{{ e(mb_strtolower($content->title.' '.$content->summary.' '.$content->topic.' '.implode(' ', $content->tags ?? []).' '.$content->author->name)) }}">
                            <div class="card-art art-{{ $content->type }}">
                                @if ($content->cover_image)<img src="{{ $content->cover_image }}" alt="" loading="lazy">@else<span class="card-art-symbol">{{ ['novel' => '✳', 'research' => '⌘', 'resource' => '▧', 'post' => '◉'][$content->type] }}</span><span class="card-art-label">{{ $content->topic ?: ucfirst($content->type) }}</span><span class="card-art-ornament"></span>@endif
                                <span class="type-label">{{ $content->status === 'draft' ? 'DRAFT · PRIVATE' : ['novel' => 'STORY', 'research' => 'RESEARCH', 'resource' => 'STUDY GUIDE', 'post' => 'COMMUNITY'][$content->type] }}</span>
                            </div>
                            <div class="card-content"><div class="card-meta"><span>{{ $content->topic ?: ucfirst($content->type) }}</span><span>·</span><span>{{ $content->published_at?->diffForHumans() }}</span></div><h3>{{ $content->title }}</h3><p>{{ $content->summary ?: \Illuminate\Support\Str::limit(strip_tags($content->body), 130) }}</p><div class="card-author"><span class="avatar avatar-small">{{ mb_substr($content->author->name, 0, 1) }}</span><span>{{ $content->author->name }}</span><button class="read-link" data-read="{{ $content->id }}">Read <span>↗</span></button></div>
                                <div class="card-footer">@if ($content->status === 'draft')<span class="draft-private">Only you can see this draft</span><button class="button button-dark draft-publish" data-publish="{{ $content->id }}">Publish draft ↗</button>@else<button class="card-action {{ in_array($content->id, $reactedIds) ? 'is-liked' : '' }}" data-react="{{ $content->id }}" aria-label="Appreciate this"><span>♡</span> <b>{{ $content->reactions_count }}</b></button><button class="card-action" data-comments="{{ $content->id }}" data-title="{{ e($content->title) }}">◯ <b>{{ $content->comments_count }}</b></button><button class="card-action save-action {{ in_array($content->id, $savedIds) ? 'is-saved' : '' }}" data-save="{{ $content->id }}" aria-label="Save to your library">{{ in_array($content->id, $savedIds) ? '▣' : '▢' }}</button>@endif</div>
                                <script type="application/json" id="content-body-{{ $content->id }}">{!! json_encode(['title' => $content->title, 'summary' => $content->summary, 'body' => $content->body, 'author' => $content->author->name, 'type' => $content->type, 'topic' => $content->topic], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                            </div>
                        </article>
                    @empty
                        <div class="empty-state"><span>✳</span><h3>The shelves are waiting.</h3><p>Be the first to share a story, resource, or idea with the community.</p><button class="button button-dark" data-open-dialog="compose-dialog">Create the first post</button></div>
                    @endforelse
                </div><div class="no-results" id="no-results" hidden>No matches yet. Try another search or topic.</div>
            </section>
            <footer class="page-footer"><a class="brand brand-footer" href="{{ route('home') }}"><span class="brand-mark">o.</span><span>openshelf</span></a><span>Read widely. Share generously. Keep wondering.</span><a href="#top" class="back-top">Back to top ↑</a></footer>
        </main>

        <aside class="right-column">
            <section class="right-card today-card"><div class="right-card-head"><span>YOUR DAILY PAUSE</span><span>✳</span></div><div class="quote-mark">“</div><blockquote>Education is not the filling of a pail, but the lighting of a fire.</blockquote><div class="quote-author">W. B. Yeats <span>· POET</span></div><div class="quote-rule"></div><div class="quote-foot">A thought to carry with you today.</div></section>
            <section class="right-card trend-card"><div class="right-card-head"><span>ON EVERYONE'S MIND</span><button data-view="home">•••</button></div>
                @php($topics = $contents->where('status', 'published')->pluck('topic')->filter()->countBy()->sortDesc()->take(4))
                @forelse ($topics as $topic => $count)<button class="trend-item" data-topic="{{ e($topic) }}"><span class="trend-rank">0{{ $loop->iteration }}</span><span class="trend-copy"><strong>{{ $topic }}</strong><small>{{ $count }} {{ \Illuminate\Support\Str::plural('contribution', $count) }}</small></span><span class="trend-arrow">↗</span></button>@empty<p class="muted-copy">Topics shared by the community will show up here.</p>@endforelse
            </section>
            <section class="right-card people-card"><div class="right-card-head"><span>PEOPLE TO LEARN WITH</span><a href="{{ url('/chatify') }}">Open Chatify ↗</a></div>
                @forelse ($members as $member)<div class="person-row"><span class="avatar">{{ mb_substr($member->name, 0, 1) }}</span><span class="person-copy"><strong>{{ $member->name }}</strong><small>{{ $member->headline ?: 'Curious community member' }}</small></span><a class="connect-button" href="{{ url('/chatify') }}" aria-label="Message {{ $member->name }}">↗</a></div>@empty<p class="muted-copy">New members will appear here. Invite a friend to learn together.</p>@endforelse
            </section>
            <div class="right-footer"><a href="#about">About</a><a href="#guidelines">Community guidelines</a><a href="#privacy">Privacy</a><span>© 2026 OpenShelf</span></div>
        </aside>
    </div>

    @if (session('status'))<div class="toast" role="status">{{ session('status') }}</div>@endif

    <dialog class="modal" id="login-dialog"><div class="modal-head"><a class="brand" href="#"><span class="brand-mark">o.</span><span>openshelf</span></a><button class="close-button" data-close-dialog aria-label="Close">×</button></div><div class="modal-body"><div class="eyebrow">WELCOME BACK</div><h2>Pick up where<br>your curiosity left off.</h2><form method="post" action="{{ route('login') }}" class="form-stack">@csrf @if ($errors->any())<div class="validation-errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<label>Email address<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><label class="check-label"><input type="checkbox" name="remember"> Keep me signed in</label><button class="button button-dark button-wide">Log in <span>↗</span></button></form><div class="oauth-options">@foreach (['google' => 'Google', 'microsoft' => 'Microsoft', 'tiktok' => 'TikTok', 'linkedin' => 'LinkedIn'] as $provider => $label)<a class="button button-outline button-wide" href="{{ route('social.redirect', $provider) }}">Continue with {{ $label }}</a>@endforeach</div><p class="switch-form">New here? <button data-switch-dialog="register-dialog">Create a free account</button></p></div></dialog>

    <dialog class="modal" id="register-dialog"><div class="modal-head"><a class="brand" href="#"><span class="brand-mark">o.</span><span>openshelf</span></a><button class="close-button" data-close-dialog aria-label="Close">×</button></div><div class="modal-body"><div class="eyebrow">A SEAT AT THE TABLE</div><h2>Come curious.<br>Leave inspired.</h2><form method="post" action="{{ route('register') }}" class="form-stack">@csrf @if ($errors->any())<div class="validation-errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<label>Your name<input type="text" name="name" value="{{ old('name') }}" required maxlength="80" autocomplete="name"></label><label>Email address<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label><label>Password <span class="field-hint">8 characters minimum</span><input type="password" name="password" required minlength="8" autocomplete="new-password"></label><label>Confirm password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label><button class="button button-dark button-wide">Join the community <span>↗</span></button></form><div class="oauth-options">@foreach (['google' => 'Google', 'microsoft' => 'Microsoft', 'tiktok' => 'TikTok', 'linkedin' => 'LinkedIn'] as $provider => $label)<a class="button button-outline button-wide" href="{{ route('social.redirect', $provider) }}">Sign up with {{ $label }}</a>@endforeach</div><p class="switch-form">Already a member? <button data-switch-dialog="login-dialog">Log in</button></p></div></dialog>

    <dialog class="modal modal-compose" id="compose-dialog"><div class="modal-head"><div><div class="eyebrow">MAKE SOMETHING, SHARE SOMETHING</div><h2>Put your idea out there.</h2></div><button class="close-button" data-close-dialog aria-label="Close">×</button></div>
        @auth
            <form method="post" action="{{ route('content.store') }}" class="form-stack compose-form">@csrf @if ($errors->any())<div class="validation-errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<label>What are you sharing?<select name="type" required><option value="post">A community post</option><option value="novel">A story or novel excerpt</option><option value="research">A research paper or summary</option><option value="resource">A study guide or learning resource</option></select></label><label>Give it a title<input name="title" value="{{ old('title') }}" required maxlength="180" placeholder="A title that invites people in"></label><label>A short introduction<textarea name="summary" rows="2" maxlength="500" placeholder="What should readers know? (optional)">{{ old('summary') }}</textarea></label><label>Your work<textarea name="body" rows="7" required maxlength="100000" placeholder="Share your story, research, notes, or questions...">{{ old('body') }}</textarea></label><div class="form-row"><label>Topic<input name="topic" value="{{ old('topic') }}" maxlength="100" placeholder="e.g. Biology, Poetry"></label><label>Tags <span class="field-hint">comma separated</span><input name="tags" value="{{ old('tags') }}" maxlength="500" placeholder="e.g. learning, beginners"></label></div><label>Cover image URL <span class="field-hint">optional</span><input name="cover_image" value="{{ old('cover_image') }}" type="url" maxlength="2048" placeholder="https://..."></label><label>Visibility<select name="status" required><option value="published">Publish now</option><option value="draft">Save as draft</option></select></label><div class="compose-submit"><span>Be kind. Credit your sources. Share what you love.</span><button class="button button-dark">Publish to OpenShelf <span>↗</span></button></div></form>
        @else
            <div class="modal-body auth-prompt"><p>Join OpenShelf to publish your own stories, research, and study resources.</p><button class="button button-dark" data-switch-dialog="register-dialog">Create a free account <span>↗</span></button></div>
        @endauth
    </dialog>

    <dialog class="modal modal-reader" id="reader-dialog"><div class="modal-head"><span class="eyebrow" id="reader-type">FROM THE LIBRARY</span><button class="close-button" data-close-dialog aria-label="Close">×</button></div><div class="reader-content"><div class="eyebrow" id="reader-topic"></div><h2 id="reader-title"></h2><p class="reader-byline" id="reader-byline"></p><p class="reader-summary" id="reader-summary"></p><div class="reader-body" id="reader-body"></div></div></dialog>

    <dialog class="modal modal-comments" id="comments-dialog"><div class="modal-head"><div><div class="eyebrow">A THOUGHTFUL CONVERSATION</div><h2 id="comments-title">Responses</h2></div><button class="close-button" data-close-dialog aria-label="Close">×</button></div><div class="comments-list" id="comments-list"></div><form class="comment-form" id="comment-form"><textarea name="body" rows="2" maxlength="2000" placeholder="Add something kind or curious..." required></textarea><button class="button button-dark">Reply <span>↗</span></button></form><div class="auth-inline" id="comment-auth"><button data-open-dialog="login-dialog">Log in</button> to join the conversation.</div></dialog>

    <dialog class="modal modal-chat" id="chat-dialog"><div class="modal-head"><div><div class="eyebrow">GOOD IDEAS TRAVEL</div><h2>Messages</h2></div><button class="close-button" data-close-dialog aria-label="Close">×</button></div><div class="chat-layout"><div class="chat-contacts">@forelse ($members as $member)<button class="contact-option" data-chat="{{ $member->id }}" data-name="{{ e($member->name) }}"><span class="avatar avatar-small">{{ mb_substr($member->name, 0, 1) }}</span><span><strong>{{ $member->name }}</strong><small>{{ $member->headline ?: 'Say hello' }}</small></span></button>@empty<p class="muted-copy">Invite a friend to start a conversation.</p>@endforelse</div><div class="chat-thread"><div class="chat-placeholder" id="chat-placeholder"><span>✳</span><p>Choose a curious mind<br>and start a conversation.</p></div><div class="chat-active" id="chat-active" hidden><div class="chat-thread-head" id="chat-recipient"></div><div class="chat-messages" id="chat-messages"></div><form class="chat-compose" id="chat-form"><input name="body" maxlength="4000" placeholder="Write a thoughtful note..." required><button aria-label="Send message">↑</button></form></div></div></div></dialog>

    <dialog class="modal modal-profile" id="profile-dialog"><div class="modal-head"><div><div class="eyebrow">YOUR OPEN SHELF</div><h2>Your account</h2></div><button class="close-button" data-close-dialog aria-label="Close">×</button></div><div class="modal-body"><p>You're part of a community built for sharing what you know and finding what you don't.</p>@auth<div class="oauth-options"><strong>Connected sign-in methods</strong>@foreach (['google' => 'Google', 'microsoft' => 'Microsoft', 'tiktok' => 'TikTok', 'linkedin' => 'LinkedIn'] as $provider => $label)@if (auth()->user()->socialAccounts->contains('provider', $provider))<p>{{ $label }} connected</p>@else<form method="post" action="{{ route('social.connect', $provider) }}">@csrf<button class="button button-outline button-wide">Connect {{ $label }}</button></form>@endif @endforeach</div><form method="post" action="{{ route('logout') }}">@csrf<button class="button button-outline button-wide">Log out</button></form>@endauth</div></dialog>
    <div class="toast toast-error" id="error-toast" role="alert" hidden></div>
    <script>window.OpenShelf = { authenticated: @json(auth()->check()), userId: @json(auth()->id()), csrf: @json(csrf_token()), saved: @json($savedIds), dialog: @json(session('openDialog')) };</script>
</body>
</html>
