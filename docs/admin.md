# Administracja

## ⬇️ Markdown

Gdziekolwiek w interfejsie znajduje się pole tekstowe opatrzone oznaczeniem `[md]`, oznacza to, że tekst tutaj wpisany, może być interpretowany przez język [Markdown](https://markdownguide.org/). Pozwala on na redagowanie opisów i tekstów o bogatej treści. Dokumentacja i przydatne informacje znajdują się w linku powyżej.

## 🦺 Logowanie

W celu zalogowania się do panelu administracyjnego, przejdź na podstronę `/admin`, a następnie podaj dane logowania.
<!-- todo Hasło może zostać zmienione poprzez formularz na odpowiedniej podstronie. -->

## ⛰️ Strony górne

Strony górne stanowią podstrony aplikacji i pozwalają na zawieranie dodatkowej treści, np. *O nas* czy *Polityka zwrotów*.

## 🗂️ Kategorie

Kategorie pozwalają grupować produkty wyświetlane w Ofertowniku.

Jeden produkt może być przypisany do więcej niż jednej kategorii. Brak przypisanych kategorii jest równoznaczny z brakiem jego widoczności w ofercie.

Kategorie produktowe mogą zostać oznaczone jako niewidoczne, co ukrywa je na liście kategorii.

Kategoria może mieć swoją kategorię nadrzędną.

## 📦 Produkty

### Import z Magazynu

Informacje o produktach  widocznych w Ofertowniku są pobierane z Magazynu.

Aby rozpocząć proces importu, należy użyć przycisku *Importuj*.
Na następnej stronie należy podać SKU produktów, które mają być zsynchronizowane.
<!-- todo Wypisanie kilku SKU po przecinku spowoduje wyszukanie informacji na temat każdego z nich. -->

W następnym kroku Ofertownik wypisze wszystkie znalezione w Magazynie produkty o odpowiadających SKU. Zaznacz produkty na liście, aby je zaimportować.
Na tym etapie możliwe jest wybranie kategorii produktowych, które zostaną przypisane do każdego z zaimportowanych produktów.

### Edycja

W edytorze produktu znajdują się dodatkowe dane niesynchronizowane z Magazynem - głównie pole *Dodatkowy opis*.

Aby edytować pozostałe dane produktu, należy przejść do jego edycji w Magazynie, zapisać zmiany, a następnie zapisać zmiany w Ofertowniku. Nowe dane zostaną ponownie zsynchronizowane pomiędzy systemami.

Edycja produktu pozwala również na przypisywanie go do kategorii produktowych.
Aby dodać kategorię, wybierz ją z listy rozwijanej.
Aby usunąć kategorię, użyj przycisku × obok nazwy kategorii.

Zmiany zostaną zapisane po przesłaniu formularza.
