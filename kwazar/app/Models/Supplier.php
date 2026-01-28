<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\ComponentAttributeBag;

class Supplier extends Model
{
    use HasFactory;

    public const ALLOWED_DISCOUNTS = [
        "Rabat na produkty" => "products_discount",
        "Rabat na znakowania" => "markings_discount",
        "Rabat na usługi dodatkowe" => "additional_services_discount",
    ];

    public const META = [
        "label" => "Dostawcy",
        "icon" => "truck",
        "description" => "",
        "role" => "technical",
        "ordering" => 11,
        // "listScope" => "", // default scope to list items in model editor, empty defaults to forAdminList
        // "defaultSort" => "", // default sort, as it appears in url
        // "defaultFltr" => "", // default filters //todo expand
    ];

    protected $fillable = [
        "name",
        "allowed_discounts",
        "custom_discounts",
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
            get: fn () => "Możliwe rabaty: " . count($this->allowed_discounts ?? []),
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
        //
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
        "allowed_discounts" => [
            "title" => "Możliwe rabaty",
            "icon" => "sale",
            "show-on" => "edit",
            "component" => "supplier.allowed-discounts",
            // "role" => "",
        ],
        "custom_discounts" => [
            "title" => "Niestandardowe rabaty dla produktów",
            "icon" => "cart-percent",
            "show-on" => "edit",
            "component" => "supplier.custom-discounts",
            // "role" => "",
        ],
    ];

    #region scopes
    use HasStandardScopes;
    #endregion

    #region attributes
    protected function casts(): array
    {
        return [
            "allowed_discounts" => "array",
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

    public function customDiscounts(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode(array_map(fn ($v) => json_decode($v), $value)),
        );
    }

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
    #endregion

    #region helpers
    #endregion
}
