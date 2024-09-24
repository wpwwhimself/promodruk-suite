@props([
    "pages",
])

<nav id="top-nav" class="flex-right">
    @foreach ([
        ["Kokpit", "dashboard", true],
        ["Moje konto", "users.me", !userIs("technical")],
        ["Pracownicy", "users.list", userIs("technical")],
        ["Oferty", "offers.offer", userIs("offer-maker")],
    ] as [$label, $route, $conditions])
    @if ($conditions)
    <a href="{{ route($route) }}"
        {{ $attributes->class([
            "active" => Route::currentRouteName() == $route,
            "button",
            "animatable",
        ]) }}
    >
        {{ $label }}
    </a>
    @endif
    @endforeach

    @auth
    <a href="{{ route("logout") }}" class="button">Wyloguj</a>
    @endauth
</nav>
