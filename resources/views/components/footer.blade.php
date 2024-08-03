<footer class="flex-right spread padded">
    <div class="flex-right middle">
        <x-logo class="small" />
        <h2>{{ getSetting("app_name") ?? "Ofertownik" }}</h2>
    </div>

    <div>
        @auth
        Zalogowano jako {{ Auth::user()->name }}
        <a href="{{ route("logout") }}">Wyloguj</a>
        <a href="{{ route('dashboard') }}">Kokpit</a>
        @endauth
    </div>
</footer>
