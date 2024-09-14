<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield("title") | {{ config("app.name") }} | Promodruk</title>

        <link rel="stylesheet" href="{{ asset("css/app.css") }}">
        @include("popper::assets")
        <link rel="stylesheet" href="{{ asset("css/ckeditor.css") }}?{{ time() }}">
        <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css">

        <script src="{{ asset("js/earlies.js") }}"></script>

        {{-- ckeditor stuff --}}
        <script type="importmap">
        {
            "imports": {
                "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
                "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
            }
        }
        </script>
        <script type="module" src="{{ asset("js/ckeditor.js") }}?{{ time() }}"></script>
    </head>
    <body>
        <div id="main-wrapper" class="flex-down">
            <h1>
                @yield("title")
                <small class="ghost">{{ config("app.name") }}</small>
            </h1>

            @auth
            <x-top-nav :pages="\App\Http\Controllers\AdminController::$pages" />
            @endauth

            <main class="flex-down">
            @yield("content")
            </main>
        </div>

        @foreach (["success", "error"] as $status)
        @if (session($status))
        <x-popup-alert :status="$status" />
        @endif
        @endforeach

        <script src="{{ asset("js/app.js") }}"></script>
    </body>
</html>
