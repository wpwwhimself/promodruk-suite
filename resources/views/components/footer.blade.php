<footer class="flex-right spread padded">
    <div>
        <h2>{{ \App\Models\Setting::find("app_name")->value ?? "Ofertownik" }}</h2>
    </div>

    <div>
        @auth
        Zalogowano jako {{ Auth::user()->name }}
        <a href="{{ route("logout") }}">Wyloguj</a>
        @endauth

        <span class="ghost">
        </span>
    </div>
</footer>
