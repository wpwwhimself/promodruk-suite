@props([
    "pages",
])

<nav id="top-nav" class="flex-right">
    @foreach ($pages as [$label, $route, $role])
    @if (userIs($role))
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
