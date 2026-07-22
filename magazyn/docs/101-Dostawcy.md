{
    "icon": "truck",
    "role": "technical"
}

# API zewnętrznych dostawców

Magazyn przewiduje integrację z różnymi zewnętrznymi źródłami danych, pochodzącymi od dostawców produktów. Dane te są wykorzystywane na potrzeby tworzenia i edytowania produktów oraz pobierania ich stanów magazynowych.

## Ogólne technikalia 🧑‍💻

Integracje definiowane są poprzez klasy integratorów, które można znaleźć w katalogu `/app/DataIntegrators`. Wszystkie integratory poszczególnych dostawców korzystają ze wspólnej klasy `ApiHandler`.

W pliku `.env` znajdują się dane dostępowe wymagane do połączenia się ze źródłami danych dostawców.

## Lista obsługiwanych dostawców

Poniższa lista zawiera informacje o szczegółach synchronizacji z poszczególnymi dostawcami.

### Anda

#### Ograniczony dostęp po IP

Anda udostępnia swoje API jedynie dla określonych IP. Dla Magazynu ten dostęp został udzielony, ale z uwagi na dynamiczne IP developera tworzenie i modyfikacje handlera muszą się odbywać przez obejście.

🧑‍💻 Klasa `ApiHandler` udostępnia funkcję `test`, która służy do podglądu pobieranych danych. Podgląd można uzyskać w przeglądarce dla adresu `/test/Anda`. Przykładowe wyciągajki danych:
- [bez parametrów](/test/Anda) - komplet danych
- [mode=product](/test/Anda?mode=product) - rozpoczęcie importu wszystkich produktów (raczej nie przejdzie z uwagi na timeout)
- [mode=product&sku=AP1121-09A](/test/Anda?mode=product&sku=AP1121-09A) - rozpoczęcie importu produktu AP1121-09A (nietestowane)
- [single=products](/test/Anda?single=products) - wyświetlenie listy produktów
- [single=products&item=2080](/test/Anda?single=products&item=2080) - wyświeltenie danych produktu o kluczu `2080`
- [single=products&itemProp=itemNumber&itemPropValue=AP1121-09A](/test/Anda?single=products&itemProp=itemNumber&itemPropValue=AP1121-09A) - wyświetlenie danych produktu o `itemNumber` równym `AP1121-09A`

### Asgard

#### Rozdrobnienie pakietów danych (ODR)

Z jakiegoś powodu API Asgardu się męczy, jeśli zbyt często się je odpytuje. To sprawia, że regularne pobieranie 17 stron produktów może być wręcz niemożliwe.

Na szczęście wszystkie moduły potrzebują danych produktu, więc można to obejść. Rytm pobierania jest odwrócony - funkcja `downloadData` pobiera po jednej stronie, potem przetwarza dane, a potem bierze kolejną, zamiast pobierać najpierw wszystkie strony danych a potem procesować całość.

🧑‍💻 W kodzie zmiany od standardowego schematu są oznaczone przez `odr` (overwritten download rhythm).

### Axpol

### Cookie

#### Dane z lokalnego pliku

Dostawca dostarcza plik XML, z którego pobierane są dane. Plik jest przechowywany w repozytorium w katalogu `integrators`. Domyślnie plik powinien mieć nazwę `cookie-produkty.xml`.
- 🧑‍💻 Nazwa pliku jest definiowana w stałej `FILE_NAME`.

### Easygifts

### Falk & Ross

### Inspirion

### Jaguar

### Macma

### Malfini

### Maxim

### Midocean

### PAR

### Texet

### USB System

#### Dane z lokalnego pliku

Dostawca dostarcza plik XML, z którego pobierane są dane. Plik jest przechowywany w repozytorium w katalogu `integrators`. Domyślnie plik powinien mieć nazwę `usb-system-products.xml`.
- 🧑‍💻 Nazwa pliku jest definiowana w stałej `FILE_NAME`.

#### Własne ID i SKU

Produkty dostawcy nie posiadają odgórnych identyfikatorów. Te są generowane przez Magazyn na podstawie kolejności w pliku.

