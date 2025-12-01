<?php

namespace App\Models;

use App\Models\Shipyard\Setting as ShipyardSetting;
use Illuminate\Support\Facades\DB;

class Setting extends ShipyardSetting
{
    public const FROM_SHIPYARD = true;

    public static function fields(): array
    {
        /**
         * * hierarchical structure of the page *
         * grouped by sections (title, subtitle, icon, identifier)
         * each section contains fields (name, label, hint, icon)
         */
        return [
            [
                "title" => "Tożsamość frontu",
                "subtitle" => "Prezentacja strony dla klienta",
                "icon" => "card-account-details-outline",
                "id" => "identity-front",
                "fields" => [
                    [
                        "name" => "app_logo_front_path",
                        "label" => "Logo",
                        "hint" => "Logo aplikacji. Wyświetlane w nagłówku i stopce strony. Maksymalne proporcje: 250:27. Szersze obrazki zostaną wizualnie zmniejszone. Plik powinien mieć format PNG.",
                        "icon" => "image",
                    ],
                    [
                        "name" => "app_favicon_front_path",
                        "label" => "Favicon",
                        "hint" => "Małe logo aplikacji. Wyświetlane na karcie przeglądarki. Zalecane proporcje 1:1. Plik powinien mieć format PNG.",
                        "icon" => "image-text",
                    ],
                ],
            ],
            [
                "title" => "Teksty reklamowe",
                "subtitle" => "Banery i karuzele",
                "icon" => "bullhorn",
                "id" => "banners",
                "fields" => [
                    [
                        "subsection_title" => "Tekst powitalny",
                        "subsection_icon" => "human-greeting",
                        "fields" => [
                            [
                                "name" => "welcome_text_visible",
                                "label" => "Widoczność",
                                "icon" => "eye",
                                "selectData" => [
                                    "options" => [
                                        ["label" => "Ukryty", "value" => 0],
                                        ["label" => "Tylko strona główna", "value" => 1],
                                        ["label" => "Widoczny", "value" => 2],
                                    ],
                                ],
                            ],
                            [
                                "name" => "welcome_text_content",
                                "label" => "Treść",
                                "icon" => "text",
                            ],
                        ],
                    ],
                    [
                        "subsection_title" => "ATF",
                        "subsection_subtitle" => "At The Front, czyli prezentacja na stronie głównej",
                        "subsection_icon" => "star",
                        "fields" => [
                            [
                                "name" => "showcase_visible",
                                "label" => "Widoczność",
                                "icon" => "eye",
                                "selectData" => [
                                    "options" => [
                                        ["label" => "Ukryty", "value" => 0],
                                        ["label" => "Tylko zalogowani", "value" => 1],
                                        ["label" => "Widoczny", "value" => 2],
                                    ],
                                ],
                            ],
                            [
                                "name" => "showcase_mode",
                                "label" => "Tryb wyświetlania",
                                "icon" => "car-shift-pattern",
                                "selectData" => [
                                    "options" => [
                                        ["label" => "Tekst", "value" => "text"],
                                        ["label" => "Tekst + film", "value" => "film"],
                                        ["label" => "Karuzela zdjęć", "value" => "carousel"],
                                    ],
                                ],
                                "hint" => "Karuzela - zdjęcia pobierane są z plików w folderze `meta/showcase/carousel`. Zdjęcia powinny być w formacie JPG lub PNG.<br>
                                    Film - film pobierany jest z plików w folderze `meta/showcase/film`. Plik powinien mieć format MP4. Jeśli jest ich tam więcej, wyświetlany jest alfabetycznie pierwszy.",
                            ],
                            [
                                "name" => "showcase_full_width_text",
                                "label" => "Treść tekstu dla pełnej szerokości",
                                "icon" => "text",
                                "hint" => "Wyświetlany dla trybu 'Tekst'",
                            ],
                            [
                                "name" => "showcase_side_text",
                                "label" => "Treść tekstu bocznego",
                                "icon" => "text",
                                "hint" => "Wyświetlany dla trybu 'Tekst + film'",
                            ],
                        ],
                    ],
                    [
                        "subsection_title" => "Baner boczny",
                        "subsection_subtitle" => "Pod boczną listą kategorii",
                        "subsection_icon" => "page-layout-sidebar-left",
                        "fields" => [
                            [
                                "name" => "side_banner_visible",
                                "label" => "Widoczność",
                                "icon" => "eye",
                                "selectData" => [
                                    "options" => [
                                        ["label" => "Ukryty", "value" => 0],
                                        ["label" => "Tylko zalogowani", "value" => 1],
                                        ["label" => "Widoczny", "value" => 2],
                                    ],
                                ],
                            ],
                            [
                                "name" => "side_banner_mode",
                                "label" => "Tryb wyświetlania",
                                "icon" => "car-shift-pattern",
                                "selectData" => [
                                    "options" => [
                                        ["label" => "Film", "value" => "film"],
                                        ["label" => "Karuzela zdjęć", "value" => "carousel"],
                                    ],
                                ],
                                "hint" => "Karuzela - zdjęcia pobierane są z plików w folderze `meta/showcase/side-carousel`. Zdjęcia powinny być w formacie JPG lub PNG.<br>
                                    Film - film pobierany jest z plików w folderze `meta/showcase/film` (tak samo jak w ATF). Plik powinien mieć format MP4. Jeśli jest ich tam więcej, wyświetlany jest alfabetycznie pierwszy.",
                            ],
                            [
                                "name" => "side_banner_heading",
                                "label" => "Nagłówek",
                                "icon" => "text",
                                "hint" => "Wyświetlany dla trybu 'Tekst'",
                            ],
                        ],
                    ],
                ]
            ],
            [
                "title" => "Produkty",
                "icon" => model_icon("products"),
                "id" => "products",
                "fields" => [
                    [
                        "name" => "related_products_visible",
                        "label" => "Widoczność produktów powiązanych",
                        "hint" => "Produkty powiązane są definiowane ręcznie dla każdego produktu i pozwalają na pokazanie ze sobą produktów należących do wspólnego zestawu. Patrz: parametry produktu.",
                        "icon" => "eye",
                        "selectData" => [
                            "options" => [
                                ["label" => "Ukryty", "value" => 0],
                                ["label" => "Tylko zalogowani", "value" => 1],
                                ["label" => "Widoczny", "value" => 2],
                            ],
                        ],
                    ],
                    [
                        "name" => "similar_products_visible",
                        "label" => "Widoczność produktów podobnych",
                        "hint" => "Produkty podobne są automatycznie wyświetlane na bazie kategorii, do której należy wskazany produkt.",
                        "icon" => "eye",
                        "selectData" => [
                            "options" => [
                                ["label" => "Ukryty", "value" => 0],
                                ["label" => "Tylko zalogowani", "value" => 1],
                                ["label" => "Widoczny", "value" => 2],
                            ],
                        ],
                    ],
                ],
            ],
            [
                "title" => "Zapytania",
                "icon" => "message-question",
                "id" => "queries",
                "fields" => [
                    [
                        "name" => "old_query_files_hours_sent",
                        "label" => "Czas przechowywania plików dla wysłanych zapytań [h]",
                        "icon" => "lock-clock",
                    ],
                    [
                        "name" => "old_query_files_hours_temporary",
                        "label" => "Czas przechowywania plików dla niewysłanych zapytań [h]",
                        "icon" => "lock-clock",
                    ],
                ]
            ]
        ];
    }
}
