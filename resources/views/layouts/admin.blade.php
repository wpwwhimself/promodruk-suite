<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config("app.name") }} | Promodruk</title>

        <link rel="stylesheet" href="{{ asset("css/app.css") }}">
    </head>
    <body>
        <div id="main-wrapper" class="flex-down">
            <h1>
                @yield("title")
                <small class="ghost">Panel administratora</small>
            </h1>

            <x-top-nav :pages="\App\Http\Controllers\AdminController::$pages" />

            <main class="flex-down">
            @yield("content")
            </main>
        </div>

        @foreach (["success", "error"] as $status)
        @if (session($status))
        <x-popup-alert :status="$status" />
        @endif
        @endforeach
    </body>
</html>

