<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="icon" type="image/png" href="{{ File::exists("storage/meta/favicon.png") ? asset("storage/meta/favicon.png") : asset("storage/meta/logo.png") }}">

    {{-- 💄 styles 💄 --}}
    <style>
    {!! \App\ShipyardTheme::getFontImportUrl() !!}

    :root {
        {!! \App\ShipyardTheme::getColors() !!}
        {!! \App\ShipyardTheme::getGhostColors() !!}
        {!! \App\ShipyardTheme::getFonts() !!}
    }

    :root {
        @if (setting("app_adaptive_dark_mode"))
        color-scheme: light dark;
        @else
        color-scheme: light;
        &:has(body.dark) {
            color-scheme: dark;
        }
        @endif
    }

    @if (setting("app_adaptive_dark_mode"))
    @media (prefers-color-scheme: dark) {
        .icon.invert-when-dark {
            filter: invert(1);
        }
    }
    @endif
    </style>
    <link rel="stylesheet" href="{{ asset("css/front.css") }}">

    {{-- 🚀 standard scripts 🚀 --}}
    <script src="{{ asset("js/Shipyard/earlies.js") }}"></script>
    <script src="{{ asset("js/earlies.js") }}"></script>
    <script defer src="{{ asset("js/Shipyard/app.js") }}"></script>
    <script defer src="{{ asset("js/app.js") }}"></script>
    {{-- 🚀 standard scripts 🚀 --}}

    <script defer src="{{ asset("js/front.js") }}"></script>

    <link rel="stylesheet" href="{{ asset("css/ckeditor.css") }}">
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/@glidejs/glide/dist/css/glide.core.min.css">
    <link rel="stylesheet" href="https://unpkg.com/@glidejs/glide/dist/css/glide.theme.min.css">
    <script src="https://unpkg.com/@glidejs/glide/dist/glide.js"></script>

    <title>
        @yield("title") |
        @hasSection ("subtitle") @yield("subtitle") | @endif
        {{ setting("app_name") ?? "Ofertownik" }}
    </title>

    <script src="{{ asset("js/start.js") }}"></script>
    @include("popper::assets")
</head>
