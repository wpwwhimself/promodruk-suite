@extends("layouts.mail")
@section("title", "Zapytanie")

@section("content")

<x-query-contents :cart="$cart" :files="$files" :request-data="$request_data" />

@endsection
