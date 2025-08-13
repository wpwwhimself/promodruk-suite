# Ofertownik

This is an app commissioned by [Promodruk](www.promodruk.pl) in order to present offered products to users.

The company sells items with custimised prints and on their shop one can order these items in bulk.
As they don't stock these items, they are being ordered from many distributors, each with their own way of communicating their stock quantities and expected future deliveries. The goal of this app is to allow presenting all products from these distributors.

## Capabilities

- 🚚 API integration with multiple different sources
- ...and that's about it. Nothing more is needed

## 🧑‍💻 Dev zone

### Wstępne wymagania

1. lista produktów z podziałem na (sub)kategorie
    1. widok kategorii
        1. na pasku adresu pełna ścieżka (`https://sklep.pl/produkty/kategoria/subkategoria/subkategoria`)
        2. układ kafelków
            1. zdjęcie (jak najlepszej jakości)
            2. kod produktu
            3. opis produktu
            4. obecny stan magazynowy (API Magazynu)
        3. sortowanie, filtrowanie, liczba produktów na stronie, stronicowanie
        4. warianty produktu pogrupowane - na liście wyświetla się jeden (losowy) wariant produktu
            1. na stronie produktu widoczne są warianty produktu (odnośniki do pozostałych)
    2. wielo-zakładkowy opis produktu  
2. składanie zamówień mailowych
    1. forma koszyka produktów
    2. formularz z danymi klienta i uwagami/pytaniami
    3. wysyłanie maila na zdefiniowany adres
3. konto administratora
    1. kokpit - liczba aktywnych produktów
    2. definiowanie (sub)kategorii
    3. widok zarządzania produktami
        1. lista produktów
            1. możliwość sortowania i filtrowania  
        2. edytor produktów
            1. dodawanie ręczne produktu
            2. edycja istniejącego produktu (ręcznie dodanego -> w pełni, zaimportowanego -> tylko opis)     
            3. usuwanie produktu
            4. ukrycie produktu
    4. import produktów
        1. integracje z API dostawców -- łącznik
    5. dodanie banerów reklamowych
4. integracja z Magazynem
    1. produkty ściągają informacje o stanach magazynowych
    2. wyświetlane na bieżąco na kafelku/stronie produktu
    3. rozwinięcie Magazynu o API i możliwość pobierania danych stamtąd
5. mobile-first - aplikacja jest responsywna mobilnie i wygląda dobrze na wąskich ekranach
