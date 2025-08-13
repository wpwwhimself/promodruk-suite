@extends("layouts.main")
@section("title", $page->name)

@section("content")

{{ \Illuminate\Mail\Markdown::parse($page->content) }}

@endsection
