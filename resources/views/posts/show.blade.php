@extends('layouts.app')

@section('content')
    <article>
        <h2>{{ $post->title }}</h2>
        <p><small>Published {{ optional($post->published_at)->toFormattedDateString() }}</small></p>
        <div>{!! nl2br(e($post->body)) !!}</div>
        <p>
            <strong>Tags:</strong>
            @foreach ($post->tags as $tag)
                <a href="{{ route('posts.index', ['tag' => $tag->slug]) }}">{{ $tag->name }}</a>@if (! $loop->last), @endif
            @endforeach
        </p>
    </article>
@endsection
