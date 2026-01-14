<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

class Product extends Model
{
    use HasFactory;

    public const META = [
        "label" => "Produkty",
        "icon" => "cart-variant",
        "description" => "",
        "role" => "product-manager",
        "ordering" => 11,
        "listScope" => "forAdminListByFamily",
    ];

    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id",
        "product_family_id",
        "front_id",
        "visible",
        "name",
        "subtitle",
        "family_name",
        "query_string",
        "description", "specification",
        "color",
        "sizes",
        "extra_filtrables",
        "brand_logo",
        "extra_description",
        "description_label",
        "images",
        "thumbnails",
        "original_sku",
        "price",
        "tabs",
        "related_product_ids",
        "hide_family_sku_on_listing",
        "is_synced_with_magazyn",
        "show_price",
    ];

    #region presentation
    /**
     * Pretty display of a model - can use components and stuff
     */
    public function __toString(): string
    {
        return $this->name ?? "aaa";
    }

    /**
     * Display for select options - text only
     */
    public function optionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => implode(" ", [
                $this->front_id,
                $this->name,
            ]),
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
                // "icon" => $this->icon ?? self::META["icon"],
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
            get: fn () => $this->product_family_id
            . view("components.product.variant-tiles-mini", [
                "product" => $this,
            ]),
        );
    }

    public function displayPreTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.product.thumbnail", [
                "product" => $this,
            ]),
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
        "hide_family_sku_on_listing" => [
            "type" => "checkbox",
            "label" => "Ukryj SKU rodziny na listingu",
            "icon" => "barcode",
            // "required" => true,
        ],
        "show_price" => [
            "type" => "checkbox",
            "label" => "Pokaż cenę",
            "icon" => "cash",
            "hint" => "To ustawienie nadpisuje wartość ustawienia z Magazynu o tej samej nazwie.",
        ],
        "extra_description" => [
            "type" => "HTML",
            "label" => "Dodatkowy opis",
            "icon" => "text",
            "hint" => "Ten tekst będzie wyświetlany przed opisem produktu pobranym z Magazynu.",
        ],
    ];

    public const CONNECTIONS = [
        "tags" => [
            "model" => ProductTag::class,
            "mode" => "many",
            // "field_name" => "",
            // "field_label" => "",
            // "readonly" => true,
        ],
    ];

    public const ACTIONS = [
        [
            "icon" => "download",
            "label" => "Import",
            "show-on" => "list",
            "route" => "products-import-init",
            "role" => "product-manager",
            // "dangerous" => true,
        ],
    ];
    #endregion

    public const SORTS = [
        "name" => [
            "label" => "Nazwa",
            "compare-using" => "field",
            "discr" => "name",
        ],
    ];

    public const FILTERS = [
        "id" => [
            "label" => "SKU",
            // "icon" => "barcode",
            "compare-using" => "field",
            "discr" => "front_id",
            "type" => "text",
            "operator" => "regexp",
        ],
        "name" => [
            "label" => "Nazwa",
            // "icon" => "account-badge",
            "compare-using" => "field",
            "discr" => "name",
            "type" => "text",
            "operator" => "regexp",
        ],
        "description" => [
            "label" => "Opis",
            // "icon" => "text",
            "compare-using" => "field",
            "discr" => "description",
            "type" => "text",
            "operator" => "regexp",
        ],
        "visible" => [
            "label" => "Widoczność",
            // "icon" => "eye",
            "compare-using" => "field",
            "discr" => "visible",
            "type" => "select",
            "selectData" => [
                "optionsFromConst" => [
                    self::class,
                    "VISIBILITIES",
                ],
                "emptyOption" => "wszystkie",
            ],
        ],
        // "category" => [
        //     "label" => "Kategoria",
        //     // "icon" =>
        //     "compare-using" => "field",

        // ],
    ];

    public const EXTRA_SECTIONS = [
        "refreshStatus" => [
            "title" => "Status odświeżania produktów",
            "icon" => "refresh",
            "show-on" => "list",
            "component" => "product-refresh-status",
            "role" => "product-manager",
        ],
    ];

    #region scopes
    use HasStandardScopes;

    public function scopeForAdminListByFamily($query, $sort = null, $filters = null)
    {
        $page = request("page", 1);
        $perPage = request("prpg", 25);

        $data = $query->sortAndFilter($sort, $filters);

        $data = $data->groupBy("product_family_id")
            ->map(fn ($group) => $group->random());

        $data = new LengthAwarePaginator(
            $data->slice($perPage * ($page - 1), $perPage),
            $data->count(),
            $perPage,
            $page,
            [
                "path" => request()->url(),
                "query" => request()->query(),
            ],
        );

        return $data;
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where("visible", ">=", Auth::check() ? 1 : 2);
    }

    public function scopeFamilyByPrefixedId(Builder $query, string $id): void
    {
        $query->where("front_id", "like", $id."%");
    }

    public function scopeQueried(Builder $query, ?string $q_string = null): void
    {
        if ($q_string === null) return;

        /**
         * dla wielu słów wyszuka wszystko, co pasuje do 1. słowa, ale wyżej na liście będą te, które mają kolejne słowa
         * działa lepiej niż explode $q_string i dla każdego słowa +...*, bo wtedy szukanie na krótkich słowach (np. A5) psuje wszystko
         *
         * nie chcę używać fulltext search, bo fajnie by jednak było, gdyby te krótkie słowa nie psuły wszystkiego
         */
        $words = explode(" ", $q_string);
        foreach ($words as $word) {
            $query->where(fn ($q) => $q
                ->orWhere("query_string", "like", "%$word%")
                ->orWhere("family_name", "like", "%$word%")
                ->orWhere("description", "like", "%$word%")
            );
        }
    }
    #endregion

    #region attributes
    protected function casts(): array
    {
        return [
            "specification" => "json",
            "images" => "collection",
            "thumbnails" => "json",
            "color" => "json",
            "sizes" => "json",
            "extra_filtrables" => "json",
            "tabs" => "json",
            "hide_family_sku_on_listing" => "boolean",
            "is_synced_with_magazyn" => "boolean",
            "show_price" => "boolean",
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
    public function modelEditButton(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.shipyard.ui.button", [
                "icon" => "pencil",
                "label" => "Edytuj",
                "action" => route("products-edit", ["id" => $this->product_family_id]),
            ])->render(),
        );
    }

    public function imageUrls(): Attribute
    {
        return Attribute::make(
            fn () => $this->images?->pluck(2),
        );
    }
    protected function thumbnails(): Attribute
    {
        return Attribute::make(fn ($value) => collect($this->image_urls)
            // ->sortKeys()
            ->map(fn ($img, $i) => json_decode($value)[$i] ?? $img)
            // ->sort(fn ($a, $b) => $this->sortByName($a, $b))
            ->values()
        );
    }
    public function coverImage(): Attribute
    {
        return Attribute::make(
            fn () => $this->images->firstWhere(fn ($img) => $img[3] ?? false)[2] ?? null,
        );
    }

    public function description(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => (Str::of($value)->stripTags()->replace("&nbsp;", "")->toString()) ? $value : null
        );
    }
    public function extraDescription(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => (Str::of($value)->stripTags()->replace("&nbsp;", "")->toString()) ? $value : null
        );
    }

    public function getMagazynDataAttribute()
    {
        return Http::get(env("MAGAZYN_API_URL") . "products/" . $this->id)->collect();
    }
    public function getFamilyVariantsListAttribute()
    {
        $family = $this->family;
        $colors = $family->pluck("color")->unique();
        return $colors;
    }
    public function getSimilarAttribute()
    {
        $data = collect();

        foreach ($this->categories as $category) {
            $data = $data->merge($category->products);
        }

        $data = $data
            ->filter(fn ($p) => $p->product_family_id != $this->product_family_id)
            ->groupBy("product_family_id")
            ->map(fn ($group) => $group->random());

        return $data;
    }
    public function getRelatedAttribute()
    {
        return (empty($this->related_product_ids))
            ? collect([])
            : Product::whereIn("id", explode(";", $this->related_product_ids))
                ->orWhereIn("product_family_id", explode(";", $this->related_product_ids))
                ->orderBy("id")
                ->get()
                ->groupBy("product_family_id")
                ->map(fn ($group) => $group->random());
    }
    public function getIsCustomAttribute()
    {
        return substr($this->product_family_id, 0, 2) == self::CUSTOM_PRODUCT_GIVEAWAY;
    }
    public function getFamilyPrefixedIdAttribute()
    {
        return ($this->is_custom)
            ? Str::replace(
                self::CUSTOM_PRODUCT_GIVEAWAY,
                Str::before($this->front_id, substr($this->product_family_id, 2, 7)),
                $this->product_family_id
            )
            : $this->product_family_id;
    }
    public function getHasNoUniqueImagesAttribute()
    {
        $images = $this->family->pluck("images");
        return $images->unique()->count() < $images->count();
    }
    public function activeTag(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tags
                ->where(fn ($q) => $q
                    ->whereNull("start_date")
                    ->orWhere("start_date", "<=", Carbon::now())
                )
                ->where(fn ($q) => $q
                    ->whereNull("end_date")
                    ->orWhere("end_date", ">=", Carbon::now())
                )
                ->where(fn ($t) => !$t->details->disabled)
                ->first()
        );
    }
    #endregion

    #region relations
    public function family()
    {
        return $this->hasMany(Product::class, "product_family_id", "product_family_id");
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)
            ->as("categoryData")
            ->withPivot("ordering")
            ->where("visible", ">=", Auth::id() ? 1 : 2);
    }

    public function tags()
    {
        return $this->belongsToMany(ProductTag::class, "product_product_tag", "product_family_id", "product_tag_id", "product_family_id", "id")
            ->as("details")
            ->withPivot("id", "start_date", "end_date", "disabled")
            ->orderByDesc("gives_priority_on_listing")
            ->orderBy("start_date");
    }
    #endregion

    #region helpers
    public const CUSTOM_PRODUCT_GIVEAWAY = "@@";

    private function sortByName($first, $second)
    {
        return Str::beforeLast(Str::afterLast($first, "/"), ".") <=> Str::beforeLast(Str::afterLast($second, "/"), ".");
    }
    #endregion
}
