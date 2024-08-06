@props([
    "pages",
])

<nav id="top-nav" class="flex-right">
    @foreach ($pages as [$label, $route])
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
    @endforeach
</nav>
