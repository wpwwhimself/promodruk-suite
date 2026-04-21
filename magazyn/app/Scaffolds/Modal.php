<?php

namespace App\Scaffolds;

use App\Scaffolds\Shipyard\Modal as ShipyardModal;

class Modal extends ShipyardModal
{
    protected static function items(): array
    {
        return [
            "add-ofertownik-price-multiplier" => [
                "heading" => "Nadaj mnożnik dla produktów",
                "target_route" => "products.ofertownik-price-multipliers.process",
                "summary_route" => "products.ofertownik-price-multipliers.summarise",
                "fields" => [
                    [
                        "type" => "heading",
                        "label" => "Które produkty otrzymają mnożnik",
                        "icon" => "cart"
                    ],
                    [
                        "type" => "paragraph",
                        "label" => "Wszystkie poniższe warunki muszą zostać spełnione. Pozostaw puste, aby pominąć.",
                        "icon" => "lightbulb-question",
                        "extra" => [
                            "class" => "ghost",
                        ]
                    ],
                    [
                        "name" => "id",
                        "type" => "text",
                        "label" => "SKU",
                        "icon" => "barcode"
                    ],
                    [
                        "name" => "name",
                        "type" => "text",
                        "label" => "Nazwa",
                        "icon" => "badge-account"
                    ],
                    [
                        "name" => "supplier",
                        "type" => "select",
                        "label" => "Dostawca",
                        "icon" => "truck",
                        "extra" => [
                            "selectData" => [
                                "optionsFromStatic" => [
                                    "App\\Models\\CustomSupplier",
                                    "allSuppliers"
                                ],
                                "emptyOption" => "Wszyscy"
                            ]
                        ]
                    ],
                    [
                        "type" => "heading",
                        "label" => "Mnożnik",
                        "icon" => "multiplication",
                    ],
                    [
                        "name" => "new_multiplier",
                        "type" => "number",
                        "label" => "Nowy mnożnik",
                        "icon" => "multiplication",
                        "extra" => [
                            "min" => 0,
                            "step" => 0.01,
                            "hint" => "Podaj nowy mnożnik. Pozostaw puste, żeby usunąć aktualny wpis."
                        ]
                    ]
                ],
            ],
            "set-ofertownik-price-multiplier-for-families" => [
                "heading" => "Nadaj nowe mnożniki",
                "target_route" => "products.ofertownik-price-multipliers.process",
                "summary_route" => "products.ofertownik-price-multipliers.summarise",
                "fields" => [
                    [
                        "name" => "new_multiplier",
                        "type" => "number",
                        "label" => "Nowy mnożnik",
                        "icon" => "multiplication",
                        "extra" => [
                            "min" => 0,
                            "step" => 0.01,
                            "hint" => "Podaj nowy mnożnik. Pozostaw puste, żeby usunąć aktualny wpis."
                        ]
                    ]
                ],
            ],
        ];
    }
}
