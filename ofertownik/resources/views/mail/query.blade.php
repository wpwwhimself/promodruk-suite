@extends("layouts.mail")
@section("title", "Zapytanie")

@section("content")

<x-query-contents :cart="$cart" :files="$files" :global-files="$global_files" :request-data="$request_data" />

@endsection
