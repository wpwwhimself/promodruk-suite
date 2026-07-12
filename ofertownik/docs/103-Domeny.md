{
    "icon": "drama-masks",
    "role": "technical"
}

# Wprowadzenie

Domeny służą wizualnemu rozróżnieniu wyglądu Ofertownika. Za ich pomocą, przechodząc do aplikacji za pomocą konkretnego adresu URL, wygląd i niektóre funkcjonalności systemu ulegają zmianie.
Ma to zastosowanie dla personalizowania wyglądu oferty dla np. poszczególnych handlowców.

Nie wyklucza to działanie podstawowego wyglądu strony, który jest określany w tradycyjny sposób, w [ustawieniach systemu](/admin/settings).

# Konfiguracja zakupionej domeny

Aby system domen mógł działać poprawnie, zakupione domeny muszą kierować użytkownika do serwera, gdzie aktualnie działa system macierzysty.

- ℹ️ Na przykład, jeśli Ofertownik działa na serwerze `10.0.0.1` i korzysta z katalogu `/home/user/public_html/ofertownik`, to taką samą konfigurację powinna otrzymać nowa domena.

# Cechy domeny

[W zarządzaniu modelami](/admin/models/domains) można tworzyć i zarządzać utworzonymi domenami.
W tym miejscu określane są parametry strony, a jej wpływ jest opisany poniżej.

## Nazwa

Nazwa wyświetla się na stronie głównej nad tekstem ATF.

## Adres główny

Określa adres do strony głównej edytowanej domeny.

## Kolory i logo

Określają schemat kolorów Ofertownika oraz logo i favicon strony.
- ⚠️ Domyślnie są to kolory i logo głównego Ofertownika.

## Podstrony

Lista pozwala na określenie, które ze zdefiniowanych [podstron](/admin/models/standard-pages) są wyświetlane w górnej nawigacji.

## Opiekunowie handlowi

Lista pozwala na wybranie, które adresy zdefiniowanych [opiekunów handlowych](/admin/models/supervisors) są wyświetlane w koszyku w formularzu zapytania.
