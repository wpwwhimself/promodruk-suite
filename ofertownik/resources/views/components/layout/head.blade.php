<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="icon" type="image/png" href="{{ File::exists("storage/meta/favicon.png") ? asset("storage/meta/favicon.png") : asset("storage/meta/logo.png") }}">

    <link rel="stylesheet" href="{{ asset("css/app.css") }}">

    <script defer src="{{ asset("js/app.js") }}"></script>
    {!! "<style>" !!}
    :root {
        @foreach (\App\Models\Setting::where("name", "like", "app\_accent\_color\__")->get() as $setting)
        --acc{{ substr($setting->name, -1) }}: {{ $setting->value }};
        @endforeach
    }
    {!! "</style>" !!}

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
        {{ getSetting("app_name") ?? "Ofertownik" }}
    </title>

    <script src="{{ asset("js/start.js") }}"></script>
    @include("popper::assets")
</head>
