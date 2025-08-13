@extends("layouts.admin")
@section("title", "Zmiana hasła")

@section("content")

<h1>@yield("title")</h1>

<form action="{{ route("process-change-password") }}" method="POST" class="flex-down center">
    @csrf

    <p>Twoje hasło musi zostać zmienione ze względów bezpieczeństwa.</p>
    <x-input-field type="password" name="password" label="Nowe hasło" autofocus />
    <x-input-field type="password" name="password_confirmation" label="Powtórz nowe hasło" />

    <button type="submit">Zmień hasło</button>
</form>

@endsection
