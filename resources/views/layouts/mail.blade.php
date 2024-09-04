<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <style>
    body{
        font-family: Calibri, sans-serif;
        font-size: 16px;
    }

    .logo {
        height: 5em;

        &.small {
            height: 2em;
        }
    }

    .thumbnail {
        max-height: 3em;
    }
    </style>
    <title>@yield("title") | {{ config("app.name") }}</title>
</head>
<body>
    @yield("content")

    <footer>
        <p>Pozdrawiamy,</p>
        <h2>Zespół promovera.pl</h2>

        <small>
            Wiadomość została wysłana systemowo stąd prosimy na nią NIE ODPOWIADAĆ.
            W przypadku kontaktu prosimy o skorzystania z danych kontaktowych zawartych <a href="http://promovera.pl/index/category/22">TUTAJ</a>.
            W związku z wprowadzeniem postanowień dot. RODO, po 25 maja 2018 zmieniliśmy zasady przetwarzania danych osobowych.
            Szczegóły (tj. m.in. kto jest administratorem danych osobowych, w jakim celu oraz w jaki sposób są przetwarzane, a także jak je zmienić lub usunąć) znajdują <a href="http://dok.promodruk.com.pl/rodo.pdf">tutaj</a>.
        </small>
    </footer>
</body>
</html>
