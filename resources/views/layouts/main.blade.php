<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    @vite(["resources/css/app.css", "resources/js/app.js"])
    <style>
    :root {
        --acc: {{ env("APP_ACCENT_HUE", 0) }}, {{ env("APP_ACCENT_SAT", 0) }};
    }
    </style>

    <title>@yield("title") | {{ config("app.name") }}</title>
</head>
<body>
    <div id="main-wrapper" class="flex-down">
        <x-header />
        <x-top-nav />
        <main>
        @yield("content")
        </main>
        <x-footer />
    </div>
</body>
</html>
