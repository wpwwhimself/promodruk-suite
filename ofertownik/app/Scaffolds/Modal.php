<?php

namespace App\Scaffolds;

use App\Scaffolds\Shipyard\Modal as ShipyardModal;

class Modal extends ShipyardModal
{
    protected static function items(): array
    {
        return [
            "update-category-ordering" => [
                "heading" => "Zmień kolejność kategorii",
                "target_route" => "categories-update-ordering",
                "fields" => [
                    [
                        "name" => "ordering",
                        "type" => "number",
                        "label" => "Nowa kolejność",
                        "icon" => "priority-high",
                    ]
                ],
            ],
            "update-tag-for-products" => [
                "heading" => "Zaktualizuj tag produktu",
                "target_route" => "product-tag-update-for-products",
                "fields" => [
                    [
                        "name" => "tag_id",
                        "type" => "select",
                        "label" => "Tag",
                        "icon" => "tag",
                        "required" => true,
                        "extra" => [
                            "selectData" => [
                                "optionsFromScope" => [
                                    "App\\Models\\ProductTag",
                                    "ordered"
                                ]
                            ]
                        ]
                    ],
                    [
                        "name" => "start_date",
                        "type" => "date",
                        "label" => "Widoczny od",
                        "icon" => "calendar-start",
                    ],
                    [
                        "name" => "end_date",
                        "type" => "date",
                        "label" => "Widoczny do",
                        "icon" => "calendar-end",
                    ],
                    [
                        "name" => "disabled",
                        "type" => "checkbox",
                        "label" => "Zawieszony",
                        "icon" => "tag-off",
                    ]
                ],
            ],
        ];
    }
}
