<!DOCTYPE html>
<html lang="en">
<x-layout.head />
<body>
    <h1>Panel administratora</h1>

    <nav class="flex-right">
    @foreach ([
        ["Ogólne", "dashboard"],
    ] as [$label, $route])
        <a href="{{ route($route) }}"
            class="{{ Route::currentRouteName() == $route ? "active" : "" }} padded"
        >
            {{ $label }}
        </a>
    @endforeach
    </nav>

    <main class="flex-down center-both">
    @yield("content")
    </main>
    <x-footer />

    @foreach (["success", "error"] as $status)
    @if (session($status))
    <x-popup-alert :status="$status" />
    @endif
    @endforeach
</body>
</html>
