@extends("layouts.mail")
@section("title", "Potwierdzenie wysłania zapytania")

@section("content")

<h2>Witaj,</h2>

<p>
    dziękujemy za założenie zapytania na stronie <a href="{{ env("APP_URL") }}">{{ env("APP_URL") }}</a>,
    które zostało właśnie wysłane na adres e-mail: {{ $supervisor->email }} do: {{ $supervisor->name }}.
    Wkrótce prześlemy kalkulację.
</p>

<h2>Twoje zapytanie:</h2>

<x-query-contents :cart="$cart" :files="$files" :global-files="$global_files" />

@endsection
