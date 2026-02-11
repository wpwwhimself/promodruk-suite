# Dodanie nowego dostawcy

1. Utworzyć handler w `app/DataIntegrators`
2. Dodać wiersz w bazie danych z `supplier_name`

# Dostępy do danych dostawców

## Anda
Instrukcja: _patrz `anda_feed_manual.pdf`_
> Działanie synchronizacji jest ograniczone przez adres IP zapytań, nadany dla serwera Promodruku.

## Asgard
Instrukcja: [Swagger](https://developers.bluecollection.eu/)

## Axpol
Instrukcja do pobrania [na stronie Axpol](https://axpol.com.pl/pl/33-DO-POBRANIA.html), w sekcji API, po zalogowaniu na konto.

## Cookie
Synchronizacja działa na podstawie pliku `integrators/cookie-produkty.xml`.

## Easygifts
Instrukcja po zalogowaniu: [link](https://webapi.easygifts.com.pl/)

## Falk & Ross
Instrukcja: _patrz `falk_ross_feed_manual.pdf`_

## Inspirion

Instrukcja: [link](https://leoapi.inspirion.eu/documentation).

## Macma
Instrukcja po zalogowaniu: [link](https://webapi.macma.pl/), [ustawienia i konfiguracja](https://shop.malfini.com/pl/pl/account/exports?tab=b2b)

## Malfini
Instrukcja po zalogowaniu: [link](https://shop.malfini.com/pl/pl/article/b2b-rest-api)

## Maxim
Kolekcja Postmana z dokumentacją: _patrz `maxim_postman_collection.json`_

## Midocean
Ustawienia i linki do dokumentacji [na stronie Midocean](https://www.midocean.com/poland/us/pln/viewdata/761026417?JumpTarget=ViewCustomerAPI-View), w sekcji _Customer API_.

## PAR
Dokumentacja po zalogowaniu [na stronie PAR](https://www.par.com.pl/users/profile), w sekcji _Dostęp do REST API_.

## Texet
Dokumentacja po zalogowaniu na [stronie Texet](https://www.texet.pl/pl/Cenniki).
- Synchronizacja korzysta z tokena API w URL, ale plik każdej marki może wymagać nieznacznie różnego klucza. Wspólną część stanowi pierwsze 67 znaków (definiowane w `.env`), pozostałe są opisane w handlerze pod `BRANDS`.

## USB System
Synchronizacja działa na podstawie pliku `integrators/usb-system-products.xml`.
