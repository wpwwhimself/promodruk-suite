# Listing produktów

Po wybraniu kategorii produktowej, system wyświetli informacje związane z tą kategorią.

- Jeśli dla wybranej kategorii są określone podkategorie, wyświetlone zostaną tylko one.
- W przeciwnym razie wyświetlona zostanie lista produktów przypisanych do tej kategorii.

## Wyszukiwarka

Wyszukiwarka pozwala filtrować listę produktów na podstawie:
- SKU
- nazwy produktu-matki
- nazwy produktu-dziecka
- nazwy wariantu
- opisu produktu

Słowa wpisywane w wyszukiwarkę będą wykorzystane do kolejności ich wyświetlania:
- pierwsze słowo **musi** znaleźć się w w/w polach
- kolejne słowa **mogą** znaleźć się w w/w polach, a jeśli się znajdą, produkt wyświetli się wyżej na liście

## Filtry i sortowanie

System przewiduje kilka obszarów manipulacji listą produktów w kategorii:

### Sortowanie

Umożliwia uporządkowanie kolejności wyświetlanych wyników.
Obecnie dostępne:
  - polecane - domyślne sortowanie biorące pod uwagę własne priorytety
  - po cenie (w obu kierunkach)

#### Własne priorytety

Możliwe jest nadanie własnej kolejności wyświetlania produktów na listingu.
Panel do zarządzania kolejnością jest dostępny z pioziomu panelu _Kategorie_ lub (po zalogowaniu) z pioziomu listingu kategorii.

### Filtry podstawowe

Umożliwiają filtrowanie produktów po jego podstawowych cechach.
Są zawsze widoczne.
Obecnie dostępne:
- dostępność (stan magazynowy - wszystkie/dostępne),
- kolor (na podstawie nazwy koloru),
- kod (tj. prefiks dostawcy)

- ⚠️ Po wybraniu filtru, **niewykorzystane** listy dostępnych opcji filtrowania zostaną ograniczone do cech produktów obecnie wyświetlanych. Pozwala to na wyświetlenie np. jedynie opcji kolorystycznych produktów o kodzie AS. Kolejne użycie filtrów ponownie ogranicza dotychczas niewykorzystane listy opcji filtrowania.

### Filtry dodatkowe

Produkty mogą mieć określone dodatkowe cechy, np. markę lub materiał wykonania.
Jeśli jakikolwiek z produktów posiada takie informacje, system pokazuje dodatkowe kryteria filtrowania listy wyników na podstawie tych cech dodatkowych.
- Każda z list filtrów dodatkowych posiada opcję "Pozostałe", która powoduje wyświetlenie produktów nieposiadających danej cechy.
