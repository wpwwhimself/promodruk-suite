@extends("layouts.main")
@section("title", $page->name)

@section("content")

<h1>{{ $page->name }}</h1>

{{ \Illuminate\Mail\Markdown::parse($page->content) }}

@endsection
