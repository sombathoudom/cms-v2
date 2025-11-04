@extends('layouts.app')

@section('content')
    <section>
        <h2>{{ $title }}</h2>
        @if($searchTerm)
            <p>Showing results for "{{ $searchTerm }}".</p>
        @endif

        @forelse ($posts as $post)
            <article>
                <h3><a href="{{ route('posts.show', $post->slug) }}">{{ $post->title }}</a></h3>
                <p>{{ $post->excerpt }}</p>
                <small>Published {{ optional($post->published_at)->diffForHumans() }}</small>
            </article>
        @empty
            <p>No posts found.</p>
        @endforelse

        {{ $posts->withQueryString()->links() }}
    </section>

    <aside>
        <h3>Archives</h3>
        <ul>
            @foreach ($archives as $archive)
                <li>
                    <a href="{{ route('posts.archive', ['year' => $archive['year'], 'month' => $archive['month']]) }}">
                        {{ \Carbon\Carbon::create($archive['year'], $archive['month'], 1)->translatedFormat('F Y') }}
                    </a>
                    ({{ $archive['total'] }})
                </li>
            @endforeach
        </ul>
    </aside>
@endsection
