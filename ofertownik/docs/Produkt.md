# Strona produktu

Strona produktu wyświetla informacje na temat konkretnego produktu.

## Nazwa i SKU

- Nazwa wybranego wariantu produktu
- SKU wybranego wariantu produktu

## Cena

Wyświetlana cena jest ceną podaną w Magazynie.
W przypadku braku podanej ceny wyświetlany jest tekst _na zapytanie_.

Na widoczność ceny wpływa parametr produktu w Magazynie _Cena widoczna (Ofertownik)_.
Jego wartość może zostać nadpisana lokalnym parametrem produktu _Cena widoczna_.

## Warianty produktu

Lista wariantów produktu dostępnych dla wybranej rodziny.
- Lista jest poprzedzona nagłówkiem _Dostępne ..._. Jako etykieta brana jest nazwa z Magazynu z pola _Warianty reprezentują_. Domyślnie jest to _kolory_.

Po najechaniu na kafelek wariantu pojawia się jego nazwa.
- Jeśli dostępne są stany magazynowe dla tego wariantu, w dymku obok nazwy wyświetlany jest również stan magazynowy.

Jeśli warianty stanowią **istotnie różne produkty**, tj. ich zdjęcia różnią się pomiędzy wariantami, kliknięcie na kafelek przenosi do strony danego wariantu.
W przeciwnym wypadku kafelki nie są klikalne.

Jeśli dostępne są stany magazynowe dla wybranego wariantu, pod listą wariantów wypisany jest stan magazynowy i (jeśli takie istnieją) wielkość i termin przewidywanej dostawy produktu.
Jeśli stan magazynowy nie jest dostępny (i nie istnieje podział produktu na rozmiary), a produkt pochodzi z importu automatycznego, wyświetlany jest tekst _Produkcja od podstaw – na zamówienie_.
W przeciwnym razie informacja nie jest wyświetlana.

## Opis i specyfikacja

Opis produktu składa się z opisu wariantu, do którego dołączany jest opis rodziny.

Pod nim (jeśli istnieje) dodawana jest specyfikacja wariantu.

## Dane do zapytania

Pola pozwalające na podanie szczegółów zapytania związanego z produktem:
- planowana ilość do wyceny
- komentarz do zapytania

Teksty opisujące w/w pola (etykiety i teksty pomocnicze) mogą zostać zmienione w ustawieniach kategorii, do której należy produkt.
Jeśli produkt należy do kilku kategorii, teksty do pól są pobierane z ustawień pierwszej z kategorii.

## ⛓️‍💥 Produkty powiązane

Jest to sposób wyświetlania innych produktów na tej samej stronie. W odróżnieniu od listy _🟩 Podobne produkty_, te są przypisywane przez użytkownika dla konkretnego produktu.

Dodanie produktów powiązanych odbywa się w panelu edycji produktu, w sekcji _🟩 Powiązane produkty_. Znajduje się tam lista rozwijana, w której można wyszukać konkretną rodzinę produktu (po nazwie lub kodzie). Po wybraniu produktu z listy wyświetlona zostaje miniatura produktu, dla potwierdzenia, że o ten produkt chodzi. Aby ostatecznie dodać produkt do listy powiązanych, należy użyć przycisku _🟦 Dodaj_. Po zapisaniu danych produktu przyciskiem _🟦 Zapisz_, nowo dodane produkty powiązane będą widoczne na stronie danego produktu.
