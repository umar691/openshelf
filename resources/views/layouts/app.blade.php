<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'OpenShelf') }} — Book marketplace</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>
<body>
    <header class="topbar"><a class="brand" href="{{ route('home') }}"><span class="brand-mark">o.</span><span>openshelf</span></a><a class="button button-outline" href="{{ route('home') }}">Community home</a></header>
    <main class="catalog-page">{{ $slot }}</main>
    @livewireScripts
</body>
</html>
