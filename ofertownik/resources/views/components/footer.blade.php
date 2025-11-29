<footer class="padded">
    <div class="max-width-wrapper flex-right spread">
        <div class="flex-right middle">
            <x-logo class="small" />
            <h2>{{ setting("app_name") ?? "Ofertownik" }}</h2>
        </div>

        <div>
            {{-- <span>Projekt i wykonanie: <a href="https://wpww.pl/">Wojciech Przybyła</a></span> --}}
            <a href="{{ route("login") }}">Administracja</a>
            @auth
            <span>Zalogowano jako {{ Auth::user()->name }}</span>
            <a href="{{ route("logout") }}">Wyloguj</a>
            <a href="{{ route('profile') }}">Kokpit</a>
            @endauth
        </div>
    </div>
</footer>
