# API zewnętrznych dostawców

Magazyn przewiduje integrację z różnymi zewnętrznymi źródłami danych, pochodzącymi od dostawców produktów. Dane te są wykorzystywane na potrzeby tworzenia i edytowania produktów oraz pobierania ich stanów magazynowych.

Integracje definiowane są poprzez klasy integratorów, które można znaleźć w katalogu `/app/DataIntegrators`. Wszystkie integratory poszczególnych dostawców korzystają ze wspólnej klasy `ApiHandler`.

W pliku `.env` znajdują się dane dostępowe wymagane do połączenia się ze źródłami danych dostawców.

## Przeszukiwanie danych na podstawie SKU

Domyślnym zachowaniem funkcji pobierających informacje o produktach jest odpytywanie każdego dostępnego dostawcy.
Niestety implementacja API części z nich nie pozwala na precyzyjne wyszukiwanie danych, co wiąże się z dłuższym czasem pracy integratora.

Produkty każdego dostawcy są wyróżnione za pomocą przedrostka. Najczęściej przedrostek ten pochodzi bezpośrednio od dostawcy, korzystając z jego schematu nazewnictwa produktów. W przypadku braku takiego schematu, przedrostek jest definiowany przez developera.
Jeżeli przedrostek sprawdzanego SKU nie jest zgodny z przedrostkiem dostawcy, odpytanie jest pomijane.
