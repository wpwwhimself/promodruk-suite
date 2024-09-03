@props([
    "pages",
])

<nav id="top-nav" class="flex-right">
    @foreach ($pages as [$label, $route])
    @if (Auth::id() == 1 || !in_array($route, ["synchronizations"]))
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
</nav>
