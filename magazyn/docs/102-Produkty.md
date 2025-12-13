{
    "icon": "cart-variant",
    "role": "technical"
}

# 📦 Produkty

Magazyn zapewnia bazę danych produktów oferowanych przez ogół systemu. Produkty utworzone tutaj mogą zostać pobrane przez inne aplikacje, aby tam wyświetlać ich wszystkie parametry (zdjęcia, cechy itp.).

## Modyfikacje względem innych aplikacji

### 💵 Dozwolone zniżki

Pole _Dozwolone zniżki_ steruje algorytmem rabatowania produktów w Kwazarze.
- Jeśli pole jest włączone, Kwazar korzysta z rabatów zdefiniowanych przez użytkownika, aby zmodyfikować cenę produktu (przed nadwyżką).
- Jeśli pole jest wyłączone, rabatowanie zostaje pominięte, a na cenę produktu wpływa jedynie wartość nadwyżki.

Za pomocą panelu _🧭 Produkty > Produkty wykluczone z rabatowania (Kwazar)_ możliwy jest podgląd wszystkich aktualnie niedostępnych do rabatowania produktów.
- Możliwe jest dodanie nowej rodziny produktów do wykluczenia za pomocą pola _🟦 Wyklucz nową rodzinę_.
- Możliwe jest przywrócenie rabatowania dla konkretnej rodziny za pomocą przycisku _🟦 Przywróć_ przy danym produkcie.

### 📂 Cena widoczna

Pole _Cena widoczna_ decyduje o tym, czy produkty w Ofertowniku wyświetlają cenę.
- ⚠️ Dla produktów z synchronizacji ustawienie jest **niedostępne do ręcznej edycji**.

### 📂 Mnożnik ceny

Pole _Mnożnik ceny_ modyfikuje cenę wyświetlaną w Ofertowniku o wskazany współczynnik.
- Dla ułatwienia edycja produktu posiada pole _Cena widoczna w Ofertowniku_, które przelicza cenę na podstawie współczynnika i wyświetla docelową wartość, jaka zostanie wyświetlona.

Za pomocą panelu _🧭 Produkty > Produkty z mnożnikiem ceny (Ofertownik)_ możliwa jest masowa edycja mnożników. Zmiany tam wprowadzone są stosowane z perspektywy rodzin produktów i stosowane dla wszystkich wariantów wskazanych rodzin.
- Możliwy jest podgląd obecnie modyfikowanych produktów za pomocą filtrowanej listy _Zmodyfikowane produkty_.
  - Mnożniki dla produktów odpowiadających zadanym filtrom mogą zostać zmodyfikowane za pomocą formularza _🟦 Popraw widoczne_.
- Możliwe jest dodanie nowych mnożników dla produktów spełniających określone wymagania za pomocą formularza _🟦 Dodaj nowe_.

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
