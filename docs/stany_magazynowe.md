# Pobieranie stanów magazynowych

Podstawowym zadaniem Magazynu jest monitorowanie i wyświetlanie informacji o aktualnych stanach magazynowych produktów pochodzących od zewnętrznych dostawców. Informacje te mogą być wyświetlone
- na stronie magazynu poprzez stronę główną,
- w Ofertowniku na widoku oferty danego produktu - za pomocą endpointu API `/api/stock/{product_code}`.

## 💄 Wygląd zwracanych danych

Zwracane dane obejmują informacje o produkcie takie, jak nazwa i zdjęcie, ale również kluczowo
- obecny stan magazynowy,
- wielkość i termin przewidywanej dostawy.

## 🧃 Pobieranie danych

Dane o stanach magazynowych są pobierane na życzenie, w momencie wysłania zapytania do magazynu.
<!-- todo Dane o stanach magazynowych pobierane są cyklicznie co określony interwał czasowy (ustawiany w panelu administracyjnym) i przechowywane w bazie danych Magazynu. Te dane są wówczas zwracane w odpowiedzi na zapytania do Magazynu. -->
