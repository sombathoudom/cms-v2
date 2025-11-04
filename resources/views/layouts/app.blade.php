<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'CMS') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head>
<body>
    <main class="container">
        <header class="grid">
            <div>
                <h1><a href="{{ route('home') }}">{{ config('app.name', 'CMS') }}</a></h1>
                <p>{{ config('app.tagline', 'Content managed with care') }}</p>
            </div>
            <form action="{{ route('posts.index') }}" method="get">
                <label for="q">Search</label>
                <input type="search" id="q" name="q" value="{{ $searchTerm ?? request('q') }}" placeholder="Search posts">
            </form>
        </header>

        @yield('content')
    </main>
</body>
</html>
