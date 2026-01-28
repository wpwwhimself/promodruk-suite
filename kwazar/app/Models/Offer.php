<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\ComponentAttributeBag;
use Mattiverse\Userstamps\Traits\Userstamps;

class Offer extends Model
{
    public const META = [
        "label" => "Oferty",
        "icon" => "file-document",
        "description" => "",
        "role" => "offer-manager",
        "ordering" => 1,
        // "listScope" => "", // default scope to list items in model editor, empty defaults to forAdminList
        // "defaultSort" => "", // default sort, as it appears in url
        // "defaultFltr" => "", // default filters //todo expand
    ];

    use HasFactory, Userstamps;

    protected $fillable = [
        "name", "notes",
        "unit_cost_visible", "gross_prices_visible", "stocks_visible",
        "positions",
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
            get: fn () => view("components.shipyard.app.model.badges", [
                "badges" => $this->badges,
            ])->render(),
        );
    }

    public function displayPreTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => null,
        );
    }

    public function displayMiddlePart(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.shipyard.app.model.connections-preview", [
                "connections" => self::getConnections(),
                "model" => $this,
            ])->render(),
        );
    }
    #endregion

    #region fields
    use HasStandardFields;

    public const FIELDS = [
        "notes" => [
            "type" => "TEXT",
            "label" => "Notatka",
            "icon" => "note",
        ],
        "unit_cost_visible" => [
            "type" => "checkbox",
            "label" => "Ceny jednostkowe",
            "icon" => "number-1-circle",
        ],
        "gross_prices_visible" => [
            "type" => "checkbox",
            "label" => "Ceny brutto",
            "icon" => "cash-multiple",
        ],
        "stocks_visible" => [
            "type" => "checkbox",
            "label" => "Stany magazynowe",
            "icon" => "package-variant-closed",
        ],
        // "positions" => [
        //     "type" => "JSON",
        //     "label" => "Pozycje",
        //     "icon" => "format-list-bulleted",
        // ],
    ];

    public const CONNECTIONS = [
        "files" => [
            "model" => OfferFile::class,
            "mode" => "many",
            // "field_name" => "",
            // "field_label" => "",
            // "readonly" => true,
        ],
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

    /**
     * extended form validation on model save
     * set result to true if everything is ok, false with message to force back with toast
     */
    // public static function validateOnSave($data): array
    // {
    //     $res = [
    //         "result" => true/false,
    //         "message" => "",
    //     ];
    //
    //     // validation...
    //
    //     return $res;
    // }

    /**
     * extended form fields autofill on model save
     * add or update fields inside $data to trigger additional changes based on existing form data
     * then return updated $data
     */
    // public static function autofillOnSave(array $data): array
    // {
    //     return $data;
    // }
    #endregion

    public const SORTS = [
        // "<name>" => [
        //     "label" => "",
        //     "compare-using" => "function|field",
        //     "discr" => "<function_name|field_name>",
        // ],
    ];

    public const FILTERS = [
        // "<name>" => [
        //     "label" => "",
        //     "icon" => "",
        //     "compare-using" => "function|field",
        //     "discr" => "<function_name|field_name>",
        //     "type" => "<input type>",
        //     "operator" => "regexp",
        //     "selectData" => [
        //     ],
        // ],
    ];

    public const EXTRA_SECTIONS = [
        // "<id>" => [
        //     "title" => "",
        //     "icon" => "",
        //     "show-on" => "<list|edit>",
        //     "component" => "<component_name>",
        //     "role" => "",
        // ],
    ];

    #region scopes
    use HasStandardScopes;
    #endregion

    #region attributes
    protected function casts(): array
    {
        return [
            "positions" => "array",
            "unit_cost_visible" => "boolean",
            "gross_prices_visible" => "boolean",
            "stocks_visible" => "boolean",
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

    //? override edit button on model list
    // public function modelEditButton(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn () => view("components.shipyard.ui.button", [
    //             "icon" => "pencil",
    //             "label" => "Edytuj",
    //             "action" => route(...),
    //         ])->render(),
    //     );
    // }
    #endregion

    #region relations
    public function files()
    {
        return $this->hasMany(OfferFile::class);
    }
    #endregion

    #region helpers
    public const FILE_QUEUE_LIMIT = 20;
    #endregion
}
