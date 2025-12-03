<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\ComponentAttributeBag;

class Supervisor extends Model
{
    use HasFactory;

    public const META = [
        "label" => "Opiekunowie handlowi",
        "icon" => "account-supervisor",
        "description" => "",
        "role" => "technical",
        "ordering" => 1,
    ];

    protected $fillable = [
        "name",
        "email",
        "visible",
    ];

    #region presentation
    /**
     * Pretty display of a model - can use components and stuff
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Display for select options - text only
     */
    public function optionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name,
        );
    }

    /**
     * Pretty display for model tiles
     */
    public function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.shipyard.app.h", [
                "lvl" => 3,
                "icon" => $this->icon ?? self::META["icon"],
                "attributes" => new ComponentAttributeBag([
                    "role" => "card-title",
                ]),
                "slot" => $this,
            ])->render(),
        );
    }

    public function displaySubtitle(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->email,
        );
    }

    public function displayMiddlePart(): Attribute
    {
        return Attribute::make(
            get: fn ($v, $attr) => view("components.shipyard.app.model.field-value", [
                "field" => "visible",
                "slot" => $attr["visible"]
                    ? "Widoczny"
                    : "Ukryty",
                "model" => $this,
            ])->render(),
        );
    }
    #endregion

    #region fields
    use HasStandardFields;

    public const FIELDS = [
        "email" => [
            "type" => "email",
            "label" => "Email",
            "hint" => "",
            "icon" => "at",
            "required" => true,
        ],
        "visible" => [
            "type" => "checkbox",
            "label" => "Widoczny",
            "hint" => "",
            "icon" => "eye",
            // "required" => true,
        ],
    ];

    public const CONNECTIONS = [
        // "<name>" => [
        //     "model" => ,
        //     "mode" => "<one|many>",
        //     // "field_name" => "",
        //     // "field_label" => "",
        //     // "readonly" => true,
        // ],
    ];

    public const ACTIONS = [
        // [
        //     "icon" => "",
        //     "label" => "",
        //     "show-on" => "<list|edit>",
        //     "route" => "",
        //     "role" => "",
        //     "dangerous" => true,
        // ],
    ];
    #endregion

    public const SORTS = [
        "name" => [
            "label" => "Nazwisko",
            "compare-using" => "field",
            "discr" => "name",
        ],
        "email" => [
            "label" => "Email",
            "compare-using" => "field",
            "discr" => "email",
        ],
        "visible" => [
            "label" => "Widoczność",
            "compare-using" => "field",
            "discr" => "visible",
        ],
    ];

    public const FILTERS = [
        "name" => [
            "label" => "Nazwisko",
            // "icon" => "",
            "compare-using" => "field",
            "discr" => "name",
            "type" => "text",
            "operator" => "regexp",
        ],
        "email" => [
            "label" => "email",
            // "icon" => "",
            "compare-using" => "field",
            "discr" => "email",
            "type" => "text",
            "operator" => "regexp",
        ],
        "visible" => [
            "label" => "Widoczność",
            // "icon" => "",
            "compare-using" => "field",
            "discr" => "visible",
            "type" => "select",
            "operator" => "=",
            "selectData" => [
                "options" => [
                    ["label" => "Tak", "value" => 1],
                    ["label" => "Nie", "value" => 0],
                ],
                "emptyOption" => "Wszystkie",
            ],
        ],
    ];

    #region scopes
    use HasStandardScopes;
    #endregion

    #region attributes
    protected function casts(): array
    {
        return [
            //
        ];
    }

    protected $appends = [

    ];

    use HasStandardAttributes;

    // public function badges(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn () => [
    //             [
    //                 "label" => "",
    //                 "icon" => "",
    //                 "class" => "",
    //                 "style" => "",
    //                 "condition" => "",
    //             ],
    //             [
    //                 "html" => "",
    //             ],
    //         ],
    //     );
    // }
    #endregion

    #region relations
    #endregion

    #region helpers
    #endregion
}
