@extends("layouts.mail")
@section("title", "Potwierdzenie wysłania zapytania")

@section("content")

<h1>Witaj,</h1>

<p>
    dziękujemy za założenie zapytania na stronie <a href="{{ env("APP_URL") }}">{{ env("APP_URL") }}</a>,
    które zostało właśnie wysłane na adres e-mail: {{ $supervisor->email }} do: {{ $supervisor->name }}.
    Wkrótce prześlemy kalkulację.
</p>

<p>
    Podsumowanie Twojego zapytania:
</p>

<x-query-contents :cart="$cart" :files="$files" :global-files="$global_files" />

@endsection
