<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="icon" type="image/png" href="{{ asset("storage/meta/logo.png") }}">

    @vite(["resources/css/app.css", "resources/js/app.js"])
    <style>
    :root {
        --acc: {{ env("APP_ACCENT_HUE", 0) }}, {{ env("APP_ACCENT_SAT", 0) }};
    }
    </style>

    <title>@yield("title") | {{ \App\Models\Setting::find("app_name")->value ?? "Ofertownik" }}</title>
</head>
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
