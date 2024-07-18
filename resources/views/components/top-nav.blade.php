@props([
    "pages",
])

<nav id="top-nav" class="flex-right">
    @foreach ($pages as [$label, $route])
    <a href="{{ route($route) }}"
        class="{{ Route::currentRouteName() == $route ? "active" : "" }} padded"
    >
        {{ $label }}
    </a>
    @endforeach
</nav>
