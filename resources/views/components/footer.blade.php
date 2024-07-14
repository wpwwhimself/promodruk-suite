<footer class="flex-right">
    <h2>{{ \App\Models\Setting::find("app_name")->value ?? "Ofertownik" }}</h2>
    @auth
    Zalogowano jako {{ Auth::user()->name }}
    <a href="{{ route("logout") }}">Wyloguj</a>
    @endauth

    <span class="ghost">
    </span>
</footer>
