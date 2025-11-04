@extends('layouts.app')

@section('content')
    <article>
        <h2>{{ $page->title }}</h2>
        <div>{!! nl2br(e($page->body)) !!}</div>
    </article>
@endsection
