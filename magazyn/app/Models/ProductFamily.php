<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

class ProductFamily extends Model
{
    use HasFactory;

    public const META = [
        "label" => "Rodziny produktów",
        "icon" => "cart",
        "description" => "",
        "role" => "",
        "ordering" => 10,
    ];

    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id",
        "original_sku",
        "name",
        "subtitle",
        "description",
        "description_label",
        "source",
        "original_category",
        "image_urls",
        "thumbnail_urls",
        "tabs",
        "alt_attributes",
        "marked_as_new",
    ];

    #region presentation
    public function __toString(): string
    {
        return $this->name;
    }

    public function optionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name,
        );
    }

    public function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.shipyard.app.h", [
                "lvl" => 3,
                "icon" => $this->icon ?? self::META["icon"],
                "attributes" => new ComponentAttributeBag([
                    "role" => "card-title",
                ]),
                "slot" => $this->name,
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
        // "<column_name>" => [
        //     "type" => "<input_type>",
        //     "columnTypes" => [ // for JSON
        //         "<label>" => "<input_type>",
        //     ],
        //     "selectData" => [ // for select
        //         "options" => ["label" => "", "value" => ""],
        //         "emptyOption" => "",
        //     ],
        //     "label" => "",
        //     "hint" => "",
        //     "icon" => "",
        //     // "required" => true,
        //     // "autofillFrom" => ["<route>", "<model_name>"],
        //     // "characterLimit" => 999, // for text fields
        //     // "hideForEntmgr" => true,
        //     // "role" => "",
        // ],
    ];

    public const CONNECTIONS = [
        // "<name>" => [
        //     "model" => ,
        //     "mode" => "<one|many>",
        //     // "field_name" => "",
        //     // "field_label" => "",
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

    // use CanBeSorted;
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
        //     "mode" => "<one|many>",
        //     "operator" => "",
        //     "options" => [
        //         "<label>" => <value>,
        //     ],
        // ],
    ];

    #region scopes
    use HasStandardScopes;
    #endregion

    #region attributes
    protected $casts = [
        "image_urls" => "json",
        "thumbnail_urls" => "json",
        "tabs" => "json",
        "alt_attributes" => "json",
        "marked_as_new" => "boolean",
    ];

    protected $appends = [
        "images",
        "thumbnails",
        "prefixed_id",
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

    public function getImagesAttribute()
    {
        return collect($this->image_urls)
            ->sortBy(0)
            ->mapWithKeys(fn ($img) => [$img[0] => $img[1]])
            // ->merge(
            //     collect(Storage::allFiles("public/products/$this->id/images"))
            //         ->map(fn ($path) => env("APP_URL") . Storage::url($path))
            // )
        ;
    }
    public function getThumbnailsAttribute()
    {
        return collect($this->thumbnail_urls)
            ->sortKeys()
            ->sort(fn ($a, $b) => Str::beforeLast($a, ".") <=> Str::beforeLast($b, "."))
            // ->merge(
            //     collect(Storage::allFiles("public/products/$this->id/thumbnails"))
            //         ->map(fn ($path) => env("APP_URL") . Storage::url($path))
            // )
        ;
    }
    public function getAnyThumbnailAttribute()
    {
        return $this->thumbnails?->first()
            ?? ($this->products?->count()
                ? $this->products->random()->thumbnails?->first()
                : null
            );
    }
    public function getIsCustomAttribute()
    {
        return Str::startsWith($this->id, self::CUSTOM_PRODUCT_GIVEAWAY);
    }
    public function getSupplierAttribute()
    {
        return $this->is_custom
            ? CustomSupplier::find(Str::after($this->source, self::CUSTOM_PRODUCT_GIVEAWAY))
            : ProductSynchronization::where("supplier_name", $this->source)->first();
    }
    public function getPrefixedIdAttribute()
    {
        return $this->is_custom
            ? Str::replace(self::CUSTOM_PRODUCT_GIVEAWAY, $this->supplier->prefix, $this->id)
            : $this->id;
    }

    public function getAltAttributeTilesAttribute()
    {
        if (!$this->alt_attributes) return [];
        return collect($this->alt_attributes["variants"])
            ->map(fn ($img, $lbl) => $this->attributeForTile($lbl));
    }
    public function getAltAttributeVariantsAttribute()
    {
        if (!$this->alt_attributes) return [];
        return array_keys($this->alt_attributes["variants"]);
    }
    #endregion

    #region relations
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    #endregion

    #region helpers
    public const CUSTOM_PRODUCT_GIVEAWAY = "@@";

    public static function newCustomProductId(): string
    {
        do {
            $random_number = Str::of(rand(0, 999999))->padLeft(6, "0");
            $id = self::CUSTOM_PRODUCT_GIVEAWAY . $random_number;
        } while (ProductFamily::where("id", $id)->exists());

        return $id;
    }

    public static function getByPrefixedId(string $prefixed_id): ProductFamily
    {
        $main_part = preg_match("/\d{6}/", $prefixed_id, $matches) ? $matches[0] : null;
        return ProductFamily::findOrFail(self::CUSTOM_PRODUCT_GIVEAWAY . $main_part);
    }

    public function attributeForTile(?string $variant_name): array
    {
        $ret = [
            "selected" => [
                "label" => "brak informacji",
                "img" => null,
            ],
            "data" => $this->alt_attributes,
        ];

        if (!$variant_name) return $ret;

        $selected = collect($this->alt_attributes["variants"])
            ->filter(fn ($img, $lbl) => $lbl == $variant_name);

        $ret["selected"]["label"] = $selected->keys()->first() ?? $variant_name;
        $ret["selected"]["img"] = $selected->first();

        return $ret;
    }
    #endregion
}
