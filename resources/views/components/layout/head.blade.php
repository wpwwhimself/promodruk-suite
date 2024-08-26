<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="icon" type="image/png" href="{{ File::exists("storage/meta/favicon.png") ? asset("storage/meta/favicon.png") : asset("storage/meta/logo.png") }}?{{ time() }}">

    <link rel="stylesheet" href="{{ asset("css/app.css") }}?{{ time() }}">
    <script defer src="{{ asset("js/app.js") }}?{{ time() }}"></script>
    {!! "<style>" !!}
    :root {
        @foreach (\App\Models\Setting::where("name", "like", "app\_accent\_color\__")->get() as $setting)
        --acc{{ substr($setting->name, -1) }}: {{ $setting->value }};
        @endforeach
    }
    {!! "</style>" !!}

    @bukStyles(true)
    <link rel="stylesheet" href="{{ asset("css/ckeditor.css") }}?{{ time() }}">
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css">

    <title>
        @yield("title") |
        @hasSection ("subtitle") @yield("subtitle") | @endif
        {{ getSetting("app_name") ?? "Ofertownik" }}
    </title>

    <script src="{{ asset("js/start.js") }}?{{ time() }}"></script>
    @include("popper::assets")
</head>
