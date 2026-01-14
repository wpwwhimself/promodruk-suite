<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\ComponentAttributeBag;

class ProductTag extends Model
{
    use HasFactory;

    public const META = [
        "label" => "Tagi produktów",
        "icon" => "tag",
        "description" => "Tagi produktów pozwalają na wyróżnienie produktów na listingu. Produkty oznaczone tagiem otrzymują wyróżnik na kafelku lub nawet utrzymują wysokie pozycje listingu.",
        "role" => "product-manager",
        "ordering" => 12,
    ];

    protected $fillable = [
        "name",
        "type",
        "ribbon_color",
        "ribbon_text",
        "ribbon_text_size_pt",
        "ribbon_text_color",
        "gives_priority_on_listing",
    ];

    #region presentation
    /**
     * Pretty display of a model - can use components and stuff
     */
    public function __toString(): string
    {
        return $this->name ?? "nienazwany tag";
    }

    /**
     * Display for select options - text only
     */
    public function optionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name . ($this->gives_priority_on_listing ? " 📌" : ""),
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
            get: fn () => $this->ribbon_text,
        );
    }

    public function displayMiddlePart(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.shipyard.app.model.badges", [
                "badges" => $this->badges,
            ])->render()
            . view("components.shipyard.app.model.connections-preview", [
                "connections" => self::getConnections(),
                "model" => $this,
            ])->render(),
        );
    }
    #endregion

    #region fields
    use HasStandardFields;

    public const FIELDS = [
        "type" => [
            "type" => "select",
            "selectData" => [ // for select
                "options" => [
                    ["label" => "u góry", "value" => "top-bar"],
                    ["label" => "w prawym rogu", "value" => "right-corner"],
                ],
            ],
            "label" => "Typ",
            "icon" => "ev-plug-type1",
            "required" => true,
        ],
        "ribbon_color" => [
            "type" => "color",
            "label" => "Kolor wstążki",
            "icon" => "palette",
            "required" => true,
        ],
        "ribbon_text" => [
            "type" => "text",
            "label" => "Tekst na wstążce",
            "icon" => "label",
            "required" => true,
        ],
        "ribbon_text_size_pt" => [
            "type" => "number",
            "label" => "Rozmiar czcionki [pt]",
            "icon" => "format-size",
            "required" => true,
            "min" => 1,
        ],
        "ribbon_text_color" => [
            "type" => "color",
            "label" => "Kolor czcionki",
            "icon" => "palette",
            "required" => true,
        ],
        "gives_priority_on_listing" => [
            "type" => "checkbox",
            "label" => "Przypina produkt",
            "icon" => "pin",
            "hint" => "Produkty z tym tagiem są na szczycie listingu.",
        ],
    ];

    public const CONNECTIONS = [
        "products" => [
            "model" => Product::class,
            "mode" => "many",
            // "field_name" => "",
            // "field_label" => "",
            "readonly" => true,
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
        "preview" => [
            "title" => "Podgląd kafelka produktu",
            "icon" => "eye",
            "component" => "product.tag-preview",
            // "role" => "product-manager",
        ],
    ];

    #region scopes
    use HasStandardScopes;

    public function scopeVisible(Builder $query): void
    {
        $query;
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->orderBy("name");
    }
    #endregion

    #region attributes
    protected function casts(): array
    {
        return [
            "gives_priority_on_listing" => "boolean",
        ];
    }

    protected $appends = [

    ];

    use HasStandardAttributes;

    public function badges(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                [
                    "label" => "Przypina produkty",
                    "icon" => "pin",
                    "class" => "accent tertiary",
                    // "style" => "",
                    "condition" => $this->gives_priority_on_listing,
                ],
                // [
                //     "html" => "",
                // ],
            ],
        );
    }
    #endregion

    #region relations
    public function products()
    {
        return $this->belongsToMany(Product::class, "product_product_tag", "product_tag_id", "product_family_id", "id", "product_family_id");
    }
    #endregion

    #region helpers
    #endregion
}
