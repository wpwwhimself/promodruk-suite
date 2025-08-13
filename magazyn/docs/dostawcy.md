# API zewnętrznych dostawców

Magazyn przewiduje integrację z różnymi zewnętrznymi źródłami danych, pochodzącymi od dostawców produktów. Dane te są wykorzystywane na potrzeby tworzenia i edytowania produktów oraz pobierania ich stanów magazynowych.

Integracje definiowane są poprzez klasy integratorów, które można znaleźć w katalogu `/app/DataIntegrators`. Wszystkie integratory poszczególnych dostawców korzystają ze wspólnej klasy `ApiHandler`.

W pliku `.env` znajdują się dane dostępowe wymagane do połączenia się ze źródłami danych dostawców.
