@extends("layouts.mail")
@section("title", "Potwierdzenie wysłania zapytania")

@section("content")

<h1>Szanowni Państwo,</h1>

<p>
    Państwa zapytanie ofertowe w sklepie Promovera zostało wysłane.
    Wkrótce skontaktujemy się z Państwem.
</p>

<p>
    Poniżej treść złożonego zapytania.
</p>

<x-query-contents :cart="$cart" :files="$files" :request-data="$request_data" />

@endsection
