# Administracja magazynem

## 🦺 Logowanie

W celu zalogowania się do panelu administracyjnego, przejdź na podstronę `/admin`, a następnie podaj dane logowania.
<!-- todo Hasło może zostać zmienione poprzez formularz na odpowiedniej podstronie. -->

## ✨ Cechy

W Magazynie definiuje się cechy podstawowe i dodatkowe.

Cechy podstawowe są listą wariantów, które pozwalają na ujednolicenie różnych wariacji produktów producentów.
W przypadku Magazynu opisuje głównie kolor produktu.
Cechy podstawowe są przypisywane do utworzonych produktów i są później wykorzystywane przez Ofertownik do pokazywania produktów powiązanych.

Cechy dodatkowe produktu pozwalają na dodatkową personalizację produktu. Reprezentują one pewne parametry produktów niezwiązane bezpośrednio z jego SKU - np. materiał wykonania, dodatkowe detale itp.
Każda cecha dodatkowa posiada jeden lub więcej wariantów i może zostać przypisana do jednego lub więcej produktów. Cecha taka może mieć charakter tekstowy, liczbowy lub kolor.

### Dodawanie i edycja

Aby dodać cechę, wybierz link na dole listy cech.
Edytor cechy pozwala określić jej nazwę, typ (tekst, liczba itd.) oraz warianty, jakie ta cecha posiada.

Aby dodać wariant, podaj jego nazwę oraz wartość, a następnie użyj akcji *Dodaj*.
Aby usunąć wariant, użyj akcji *Usuń*.
Wszystkie zmiany zostaną zapisane po zapisaniu formularza.

## 📦 Produkty

Magazyn zapewnia bazę danych produktów oferowanych przez ogół systemu. Produkty utworzone tutaj mogą zostać pobrane przez Ofertownik, aby tam wyświetlać ich wszystkie własności (zdjęcia, cechy itp.).

### Import produktów od zewnętrznego dostawcy

Import produktów na podstawie źródeł danych zewnętrznych dostawców jest definiowany przez developera na podstawie odpowiednich integratorów.
Lista obecnie obsługiwanych dostawców znajduje się w sekcji _Synchronizacje_.

Synchronizacja przechodzi kolejno przez wszystkie źródła danych i pobiera wszystkie ustalone informacje, zapisując je w bazie danych. Po zakończeniu pracy synchronizacja powraca na początek listy. Jeśli włączona była integracja produktów, zostaje ona wyłączona, aby ograniczyć zużycie zasobów.

Synchronizacja dzieli się na pobieranie danych o produktach (opisy, zdjęcia) oraz stanów magazynowych (aktualny, przyszła dostawa). Każdą z nich można włączyć indywidualnie.

### Dodawanie produktu

Do utworzenia nowego produktu służy link na dole strony listującej istniejące produkty.

Aby stworzyć nową pozycję, należy określić:
- SKU - ID produktu
- Nazwa - Tytuł produktu
- Opis (opcj.)

Po utworzeniu produktu następuje przekierowanie do edycji tegoż produktu.

### Edycja produktu

Edytor produktu, poza definiowaniem SKU, nazwy i opisu, pozwala na dodawanie zdjęć i wcześniej utworzonych cech produktu.

Aby dodać zdjęcia, użyj przycisku *Przeglądaj...*, wybierz zdjęcia, jakie chcesz dodać, a następnie zatwierdź.
Aby usunąć zdjęcia, użyj akcji *Usuń* obok danego zdjęcia.
Wszystkie zmiany zostaną wprowadzone po zapisaniu formularza.

Aby dodać cechę, wybierz jej nazwę z listy rozwijanej, a następnie użyj akcji *Dodaj*.
Aby usunąć cechę z produktu, użyj akcji *Usuń*.
Wszystkie zmiany zostaną wprowadzone po zapisaniu formularza.
