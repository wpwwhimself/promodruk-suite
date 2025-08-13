# Produkty własne

## SKU, prefiksy i rozpoznawanie produktów

Od strony bazodanowej wszystkie _produkty własne_, tj. produkty utworzone ręcznie, posiadają prefix `@@`.
Pozwala to na szybkie wykrywanie tego typu produktów w bazie danych.

Od strony frontu natomiast wprowadzono rozpoznawanie SKU produktów na podstawie finalnego SKU, tj. takiego z dodanym prefiksem dostawcy.

Finalne SKU dla _rodzin produktów_ budowane jest przez podmianę znacznika `@@` na prefiks dostawcy.
Finalne SKU dla _produktów_ dodatkowo bierze pod uwagę, czy produkt jest jedynym wariantem tej rodziny - jeśli tak, ukrywany jest sufiks wariantu.

> **Przykład**
> Produkt od dostawcy o prefiksie `TEST` składa się z jednego wariantu:
> - w bazie danych wariant ma id `@@123.456-00` i rodzinę `@@123.456`
> - na froncie jest oznaczony jako `TEST123.456` dla rodziny `TEST123.456`
>
> Ten sam produkt ma dwa warianty:
> - pierwszy z wariantów w bazie to `@@123.456-01`, drugi `@@123.456-02`
> - na froncie są one oznaczone kolejno jako `TEST123.456-01` i `TEST123.456-02`
