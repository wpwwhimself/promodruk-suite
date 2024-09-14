@extends("layouts.app")
@section("title", "Zmiana hasła")

@section("content")

<section>
    <p>Twoje hasło musi zostać zmienione ze względów bezpieczeństwa.</p>

    <form action="{{ route("change-password") }}" method="POST" class="flex-down center middle">
        @csrf
        <x-input-field type="password" name="password" label="Nowe hasło" autofocus />
        <x-input-field type="password" name="password_confirmation" label="Powtórz nowe hasło" />

        <button type="submit">Zmień hasło</button>
    </form>
</section>

@endsection
