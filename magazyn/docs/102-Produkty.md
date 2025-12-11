{
    "icon": "cart-variant",
    "role": "technical"
}

# 📦 Produkty

Magazyn zapewnia bazę danych produktów oferowanych przez ogół systemu. Produkty utworzone tutaj mogą zostać pobrane przez Ofertownik, aby tam wyświetlać ich wszystkie własności (zdjęcia, cechy itp.).

## Import produktów od zewnętrznego dostawcy

Import produktów na podstawie źródeł danych zewnętrznych dostawców jest definiowany przez developera na podstawie odpowiednich integratorów.
Lista obecnie obsługiwanych dostawców znajduje się w sekcji _Synchronizacje_.

Synchronizacja przechodzi kolejno przez wszystkie źródła danych i pobiera wszystkie ustalone informacje, zapisując je w bazie danych. Po zakończeniu pracy synchronizacja powraca na początek listy. Jeśli włączona była integracja produktów, zostaje ona wyłączona, aby ograniczyć zużycie zasobów.

Synchronizacja dzieli się na pobieranie danych o produktach (opisy, zdjęcia) oraz stanów magazynowych (aktualny, przyszła dostawa). Każdą z nich można włączyć indywidualnie.

## Produkty własne

### SKU, prefiksy i rozpoznawanie produktów

Od strony bazodanowej wszystkie _produkty własne_, tj. produkty utworzone ręcznie, posiadają prefix `@@`.
Pozwala to na szybkie wykrywanie tego typu produktów w bazie danych.

Od strony frontu natomiast wprowadzono rozpoznawanie SKU produktów na podstawie finalnego SKU, tj. takiego z dodanym prefiksem dostawcy.

Finalne SKU dla _rodzin produktów_ budowane jest przez podmianę znacznika `@@` na prefiks dostawcy.
Finalne SKU dla _produktów_ dodatkowo bierze pod uwagę, czy produkt jest jedynym wariantem tej rodziny - jeśli tak, ukrywany jest sufiks wariantu.

> **Przykład**
> 
> Produkt od dostawcy o prefiksie `TEST` składa się z jednego wariantu:
> - w bazie danych wariant ma id `@@123456-00` i rodzinę `@@123456`
> - na froncie jest oznaczony jako `TEST123456` dla rodziny `TEST123456`
>
> Ten sam produkt ma dwa warianty:
> - pierwszy z wariantów w bazie to `@@123456-01`, drugi `@@123456-02`
> - na froncie są one oznaczone kolejno jako `TEST123456-01` i `TEST123456-02`
