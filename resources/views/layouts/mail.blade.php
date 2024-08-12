<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <style>
    @import url('https://fonts.cdnfonts.com/css/amazon-ember');

    header, footer {
        display: table-row;

        & > * {
            display: table-cell;
        }
    }

    body{
        font-family: "Amazon Ember", sans-serif;
        font-size: 16px;
    }

    .logo {
        max-height: 5em;

        &.small {
            max-height: 2em;
        }
    }
    </style>
    <title>@yield("title") | {{ config("app.name") }}</title>
</head>
<body>
    <header>
        <x-logo />
        <h1>@yield("title")</h1>
    </header>

    @yield("content")

    <footer>
        <x-logo class="small" />
        <h2>{{ getSetting("app_name") ?? "Ofertownik" }}</h2>
    </footer>
</body>
</html>
