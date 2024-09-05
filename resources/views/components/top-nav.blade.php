@props([
    "pages",
])

<nav id="top-nav" class="flex-right">
    @foreach ($pages as [$label, $route, $role])
    @if (userIs($role))
    <a href="{{ route($route) }}"
        {{ $attributes->class([
            "active" => Route::currentRouteName() == $route,
            "padded",
            "animatable",
        ]) }}
    >
        {{ $label }}

        @if ($route == "attributes" && \App\Models\MainAttribute::where("color", "")->count() > 0)
        <span class="danger">(!)</span>
        @endif
    </a>
    @endif
    @endforeach

    @auth
    <a href="{{ route("logout") }}">Wyloguj</a>
    @endauth
</nav>
