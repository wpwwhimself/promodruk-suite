<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

class Product extends Model
{
    use HasFactory;

    public const META = [
        "label" => "Produkty",
        "icon" => "cart-variant",
        "description" => "",
        "role" => "",
        "ordering" => 11,
    ];

    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id",
        "name",
        "description", "specification",
        "product_family_id",
        "original_sku",
        "variant_name",
        "sizes",
        "extra_filtrables",
        "brand_logo",
        "image_urls",
        "thumbnail_urls",
        "price", "ofertownik_price", "show_price",
        "manipulation_cost",
        "additional_services",
        "tabs",
        "enable_discount",
        "import_id",
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
        "specification" => "json",
        "image_urls" => "json",
        "thumbnail_urls" => "json",
        "show_price" => "boolean",
        "additional_services" => "json",
        "tabs" => "json",
        "sizes" => "json",
        "extra_filtrables" => "json",
    ];

    protected $appends = [
        "images",
        "thumbnails",
        "color",
        "variant_data",
        "front_id",
        "combined_images",
        "combined_thumbnails",
        "combined_description",
        "combined_tabs",
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
            ->sortKeys()
            // ->sort(fn ($a, $b) => Str::beforeLast($a, ".") <=> Str::beforeLast($b, "."))
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
            // ->sort(fn ($a, $b) => Str::beforeLast($a, ".") <=> Str::beforeLast($b, "."))
            // ->merge(
            //     collect(Storage::allFiles("public/products/$this->id/thumbnails"))
            //         ->map(fn ($path) => env("APP_URL") . Storage::url($path))
            // )
        ;
    }
    public function getColorAttribute()
    {
        $primary_color = PrimaryColor::where("name", $this->variant_name)->first();
        $original_color = MainAttribute::where("name", "like", "%$this->variant_name%")->first();

        foreach ([
            [empty($this->variant_name), MainAttribute::invalidColor()],
            [$primary_color, $primary_color],
            [$original_color, $original_color->primaryColor ?? $original_color],
        ] as [$case, $ret]) {
            if ($case) return $ret;
        }

        return MainAttribute::invalidColor();
    }
    public function getVariantDataAttribute()
    {
        if (!$this->productFamily->alt_attributes) return $this->color;

        $data = $this->attribute_for_tile;

        return [
            "name" => $data["selected"]["label"],
            "img" => $data["selected"]["img"],
            "large_tiles" => $data["data"]["large_tiles"] ?? false,
            "attribute_name" => $data["data"]["name"],
            "id" => null,
        ];
    }

    public function getCombinedImagesAttribute()
    {
        // give both variant and family images similar structure (see ProductFamily images attr)
        $variant_images = collect($this->image_urls)
            ->map(fn ($url, $i) => [
                "1-variant",
                $i,
                $url,
                false,
            ])
            ->values();
        $family_images = collect($this->productFamily->image_urls)
            ->map(fn ($imgdata) => [
                "2-family",
                (int) $imgdata[0],
                $imgdata[1],
                $imgdata[2] ?? false,
            ]);

        return collect($variant_images)->merge($family_images)
            ->sortBy([
                // fn ($a, $b) => -((int) $a[3] <=> (int) $b[3]), // cover images first
                fn ($a, $b) => $a[0] <=> $b[0], // variant images first
                fn ($a, $b) => $a[1] <=> $b[1], // lower indices first
            ])
            ->values();
    }
    public function getCombinedThumbnailsAttribute()
    {
        return collect($this->thumbnails)->merge($this->productFamily->thumbnails);
    }
    public function getCombinedDescriptionAttribute()
    {
        return collect($this->description)->merge($this->productFamily->description)->join("<br>");
    }
    public function getCombinedTabsAttribute()
    {
        return collect($this->tabs)->merge($this->productFamily->tabs);
    }

    public function getIdSuffixAttribute()
    {
        return Str::after($this->id, $this->product_family_id);
    }

    public function getIsCustomAttribute()
    {
        return $this->productFamily->is_custom ?? null;
    }
    public function getSupplierAttribute()
    {
        return $this->productFamily->supplier ?? null;
    }
    public function getIsOnlyVariantAttribute()
    {
        return Product::where("product_family_id", $this->product_family_id)->count() == 1;
    }
    public function getFrontIdAttribute()
    {
        return $this->is_custom
            ? ($this->is_only_variant
                ? Str::of($this->id)->replace(ProductFamily::CUSTOM_PRODUCT_GIVEAWAY, $this->supplier->prefix)->before(self::CUSTOM_PRODUCT_VARIANT_SUFFIX_SEPARATOR)
                : Str::replace(ProductFamily::CUSTOM_PRODUCT_GIVEAWAY, $this->supplier->prefix, $this->id)
            )
            : $this->id;
    }
    public function getAttributeForTileAttribute()
    {
        if (!$this->productFamily->alt_attributes) return null;
        return $this->productFamily->attributeForTile($this->variant_name);
    }
    public function getAllStocksAttribute()
    {
        return Stock::where("id", "like", $this->id."%")->get();
    }
    #endregion

    #region relations
    public function productFamily()
    {
        return $this->belongsTo(ProductFamily::class);
    }
    public function stock()
    {
        return $this->hasOne(Stock::class, "id");
    }
    public function markings()
    {
        return $this->hasMany(ProductMarking::class);
    }
    #endregion

    #region helpers
    public const CUSTOM_PRODUCT_VARIANT_SUFFIX_SEPARATOR = "-";

    public static function newCustomProductVariantSuffix(string $family_id): string
    {
        $ret = ProductFamily::find($family_id)->products
            ->pluck("id")
            ->map(fn ($id) => (int) Str::after($id, self::CUSTOM_PRODUCT_VARIANT_SUFFIX_SEPARATOR))
            ->sort()
            ->last();
        $ret = self::CUSTOM_PRODUCT_VARIANT_SUFFIX_SEPARATOR . Str::padLeft($ret + 1, 2, "0");
        return $ret;
    }

    public static function getByFrontId(string $front_id): Product
    {
        $main_part = preg_match("/\d{6}/", $front_id, $matches) ? $matches[0] : null;
        $products = Product::where("product_family_id", ProductFamily::CUSTOM_PRODUCT_GIVEAWAY . $main_part)->get();

        if ($products->count() == 1) return $products->first();

        $suffix = substr($front_id, -3);
        return $products->firstWhere(fn ($p) => Str::endsWith($p->id, $suffix));
    }
    #endregion
}
