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

    @bukStyles(true)

    <title>@yield("title") | {{ \App\Models\Setting::find("app_name")->value ?? "Ofertownik" }}</title>
</head>
<body>
    <div id="main-wrapper" class="flex-down">
        <x-header />
        <x-top-nav />
        <main>
        @yield("content")
        </main>
    </div>
    <x-footer />

    @foreach (["success", "error"] as $status)
    @if (session($status))
    <x-popup-alert :status="$status" />
    @endif
    @endforeach

    @bukScripts(true)
</body>
</html>
