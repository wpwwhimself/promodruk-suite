<footer class="padded">
    <div class="max-width-wrapper flex-right spread">
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
    </div>
</footer>
