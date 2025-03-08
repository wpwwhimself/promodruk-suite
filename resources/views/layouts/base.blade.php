<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield("title") | {{ config("app.name") }} | Promodruk</title>

        <link rel="stylesheet" href="{{ asset("css/app.css") }}">
        <link rel="stylesheet" href="{{ asset("css/ckeditor.css") }}?{{ time() }}">
        <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css">

        <script src="{{ asset("js/earlies.js") }}"></script>

        @env (["local", "stage"])
        <style>
        :root {
            @env ("local")
            --test-color: #0f0;
            @endenv
            @env ("stage")
            --test-color: #ff0;
            @endenv
        }
        header {
            background: repeating-linear-gradient(45deg, var(--test-color), var(--test-color) 25px, #000 25px, #000 50px) !important;
        }
        </style>
        @endenv

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
        @include("popper::assets")
    </head>
    <body>
        <div id="main-wrapper" class="flex-down">
            <header class="flex-right middle stretch">
                <h1>
                    @yield("title")
                    <small class="ghost">{{ config("app.name") }}</small>
                </h1>

                @auth
                <x-top-nav :pages="\App\Http\Controllers\AdminController::$pages" />
                @endauth
            </header>

            <main class="flex-down">
            @yield("content")
            </main>

            <footer class="flex-right stretch">
                <span>
                    @foreach (["success", "error"] as $status)
                    @if (session($status))
                    <x-popup-alert :status="$status" />
                    @endif
                    @endforeach
                </span>

                <span>
                    <a href="/">{{ config("app.name") }}</a>
                    {{-- <span>Projekt i wykonanie: <a href="https://wpww.pl/">Wojciech Przybyła</a></span> --}}
                    @if (Auth::check() && userIs("Administrator"))
                    <a href="https://github.com/wpwwhimself/promodruk-magazyn/tree/main/docs">Dokumentacja</a>
                    <a href="/admin/log-reader">Logi</a>
                    @endif
                </span>
            </footer>
        </div>

        <script src="{{ asset("js/app.js") }}"></script>
    </body>
</html>
