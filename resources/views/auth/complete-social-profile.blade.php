<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Finish signing in — OpenShelf</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="social-complete">
        <a class="brand" href="{{ route('home') }}"><span class="brand-mark">o.</span><span>openshelf</span></a>
        <section class="social-complete-card">
            <div class="eyebrow">ONE LAST STEP</div>
            <h1>Finish your {{ $providerLabel }} sign-in.</h1>
            <p>This provider did not share an email address with OpenShelf. Add one to create your account. It will remain unverified until you verify it separately.</p>
            <form method="post" action="{{ route('social.complete-profile.store') }}" class="form-stack">
                @csrf
                <label>Email address
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                </label>
                @error('email')<p class="field-hint">{{ $message }}</p>@enderror
                <button class="button button-dark button-wide">Continue to OpenShelf <span>↗</span></button>
            </form>
            <p class="switch-form"><a href="{{ route('home') }}">Cancel and return home</a></p>
        </section>
    </main>
</body>
</html>
