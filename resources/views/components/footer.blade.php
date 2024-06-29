<footer>
    <h2>{{ config("app.name") }}</h2>
    @auth
    Zalogowano jako {{ Auth::user()->name }}
    <a href="{{ route("logout") }}">Wyloguj</a>
    @endauth
</footer>
