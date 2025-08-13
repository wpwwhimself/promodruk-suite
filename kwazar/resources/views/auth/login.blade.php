@extends("layouts.app")
@section("title", "Logowanie")

@section("content")

<section>
    <form action="{{ route("authenticate") }}" method="POST" class="flex-down center middle">
        @csrf
        <x-input-field type="text" name="login" label="Login" autofocus autocomplete="off" />
        <x-input-field type="password" name="password" label="Hasło" />

        <button type="submit">Zaloguj</button>
    </form>
</section>

@endsection
