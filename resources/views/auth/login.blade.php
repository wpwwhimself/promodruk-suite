@extends("layouts.admin")
@section("title", "Logowanie")

@section("content")

<h1>@yield("title")</h1>

<form action="{{ route("authenticate") }}" method="POST" class="flex-down center">
    @csrf
    <x-input-field type="text" name="name" label="Login" autofocus />
    <x-input-field type="password" name="password" label="Hasło" />

    <button type="submit">Zaloguj</button>
</form>

@endsection
