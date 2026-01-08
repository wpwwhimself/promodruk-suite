<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

class Category extends Model
{
    use HasFactory;

    public const META = [
        "label" => "Kategorie",
        "icon" => "file-tree",
        "description" => "",
        "role" => "product-manager",
        "ordering" => 11,
        "defaultSort" => "ordering",
    ];

    protected $table = "categories";

    protected $fillable = [
        "name", "label", "description", "welcome_text",
        "thumbnail_link", "external_link",
        "visible", "ordering", "parent_id",
        "banners",
        "product_form_field_amounts_enabled", "product_form_field_amounts_label", "product_form_field_amounts_placeholder", "product_form_field_comment_enabled", "product_form_field_comment_label", "product_form_field_comment_placeholder",
        "slug",
    ];

    #region presentation
    /**
     * Pretty display of a model - can use components and stuff
     */
    public function __toString(): string
    {
        return $this->name ?? "";
    }

    /**
     * Display for select options - text only
     */
    public function optionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->breadcrumbs,
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
            get: fn () => $this->breadcrumbs,
        );
    }

    public function displayMiddlePart(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.shipyard.app.model.fields-preview", [
                "fields" => [
                    "visible",
                    "ordering"
                ],
                "model" => $this,
            ])->render()
            . view("components.shipyard.ui.button", [
                "icon" => "priority-high",
                "pop" => "Zmień kolejność",
                "action" => "none",
                "attributes" => new ComponentAttributeBag([
                    "class" => "tertiary",
                    "onclick" => "openModal('update-category-ordering', { id: ".$this->id.", ordering: ".($this->ordering ?? 'null')." })",
                ]),
                "slot" => null,
            ])->render(),
        );
    }
    #endregion

    #region fields
    use HasStandardFields;

    public const FIELDS = [
        "ordering" => [
            "type" => "number",
            "label" => "Wymuś kolejność",
            "icon" => "priority-high",
        ],
        "description" => [
            "type" => "HTML",
            "label" => "Opis",
            "icon" => "text",
        ],
        "welcome_text" => [
            "type" => "HTML",
            "label" => "Tekst powitalny",
            "icon" => "human-greeting",
            "hint" => "Wyświetlany jako pierwszy tekst po tytule na stronie kategorii."
        ],
        "thumbnail_link" => [
            "type" => "url",
            "label" => "Miniatura",
            "icon" => "image",
        ],
        "banners" => [
            "type" => "JSON",
            "columnTypes" => [
                "Kolejność" => "number",
                "Link" => "url",
            ],
            "label" => "Banery",
            "icon" => "format-line-weight",
            "hint" => " Zalecane wymiary baneru to 1016 × 200 px. Obrazki przekraczające te proporcje zostaną przeskalowane tak, aby zawierały się w całości karuzeli.",
        ],
        "external_link" => [
            "type" => "url",
            "label" => "Link zewnętrzny",
            "icon" => "link",
        ],
        "parent_id" => [
            "type" => "select",
            "label" => "Kategoria nadrzędna",
            "icon" => "file-tree",
            "selectData" => [
                "optionsFromScope" => [
                    Category::class,
                    "forConnection",
                    "breadcrumbs",
                    "id",
                ],
                "emptyOption" => "brak (główna)",
            ],
        ],
        "product_form_field_amounts_enabled" => [
            "type" => "checkbox",
            "label" => "Wyt. do zap. | Ilości | Aktywny",
            "icon" => "eye",
            "hint" => "Decyduje o tym, czy na stronie produktu należącego do tej kategorii w wytycznych do zapytania wyświetlane jest pole 'Ilość'.",
        ],
        "product_form_field_amounts_label" => [
            "type" => "text",
            "label" => "Wyt. do zap. | Ilości | Etykieta",
            "icon" => "label",
            "hint" => "Nazwa pola 'Ilość' w wytycznych do zapytania.",
            "placeholder" => "Planowane ilości",
        ],
        "product_form_field_amounts_placeholder" => [
            "type" => "TEXT",
            "label" => "Wyt. do zap. | Ilości | Tekst pom.",
            "icon" => "select-place",
            "hint" => "Pomocnicza zawartość pola 'Ilości' w wytycznych do zapytania.",
            "placeholder" => "np. 100/200/300 lub żółty:100 szt., zielony:50 szt.",
        ],
        "product_form_field_comment_enabled" => [
            "type" => "checkbox",
            "label" => "Wyt. do zap. | Komentarz | Aktywny",
            "icon" => "eye",
            "hint" => "Decyduje o tym, czy na stronie produktu należącego do tej kategorii w wytycznych do zapytania wyświetlane jest pole 'Komentarz'.",
        ],
        "product_form_field_comment_label" => [
            "type" => "text",
            "label" => "Wyt. do zap. | Komentarz | Etykieta",
            "icon" => "label",
            "hint" => "Nazwa pola 'Komentarz' w wytycznych do zapytania.",
            "placeholder" => "Komentarz do zapytania",
        ],
        "product_form_field_comment_placeholder" => [
            "type" => "TEXT",
            "label" => "Wyt. do zap. | Komentarz | Tekst pom.",
            "icon" => "select-place",
            "hint" => "Pomocnicza zawartość pola 'Komentarz' w wytycznych do zapytania.",
            "placeholder" => "np. dotyczący znakowania lub specyfikacji zapytania",
        ],
    ];

    public const CONNECTIONS = [
        "related" => [
            "model" => Category::class,
            "mode" => "many",
            // "field_name" => "",
            "field_label" => "Kategorie powiązane",
            // "readonly" => true,
        ],
    ];

    public const ACTIONS = [
        [
            "icon" => "sort",
            "label" => "Zarządzanie kolejnością produktów w kategorii",
            "show-on" => "list",
            "route" => "products-ordering-manage",
            "role" => "product-manager",
        ],
        [
            "icon" => "anchor",
            "label" => "Zarządzanie przypisanie produktów do kategorii",
            "show-on" => "list",
            "route" => "products-category-assignment-manage",
            "role" => "product-manager",
        ],
    ];

    /**
     * extended form validation on model save
     * set result to true if everything is ok, false with message to force back with toast
     */
    public static function validateOnSave(array $data): array
    {
        $res = [
            "result" => true,
            "message" => "",
        ];

        // disallow same-named category as a sibling - it breaks routing
        $similar_named_category = Category::where("parent_id", $data["parent_id"] ?? null)
            ->where("name", $data["name"])
            ->first();
        if ($similar_named_category && $similar_named_category->id != $data["id"]) {
            $res = [
                "result" => false,
                "message" => "Istnieje już kategoria o tej samej nazwie/ścieżce. Nie można zapisać.",
            ];
        }

        return $res;
    }

    /**
     * extended form fields autofill on model save
     * add or update fields inside $data to trigger additional changes based on existing form data
     * then return updated $data
     */
    public static function autofillOnSave(array $data): array
    {
        $data["slug"] = implode("/", array_filter([
            Category::find($data["parent_id"])?->slug,
            Str::slug($data["name"]),
        ]));

        return $data;
    }
    #endregion

    public const SORTS = [
        "name" => [
            "label" => "Nazwa",
            "compare-using" => "field",
            "discr" => "name",
        ],
        "ordering" => [
            "label" => "Kolejność",
            "compare-using" => "function",
            "discr" => "orderingNullsLast",
        ],
    ];

    public const FILTERS = [
        "parent" => [
            "label" => "Kategoria nadrzędna",
            "icon" => "file-tree",
            "compare-using" => "field",
            "discr" => "parent_id",
            "type" => "select",
            "operator" => "=",
            "selectData" => [
                "optionsFromScope" => [
                    Category::class,
                    "forConnection",
                    "option_label",
                    "id",
                ],
                "emptyOption" => "brak",
            ],
            "allowNulls" => true,
        ],
    ];

    #region scopes
    use HasStandardScopes;

    public function scopeVisible(Builder $query)
    {
        return $query->where("visible", ">=", Auth::check() ? 1 : 2);
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->orderByRaw("case when ordering is null then 999 else ordering end")
            ->orderBy("name");
    }

    public function scopeForTiles(Builder $query)
    {
        return $query->select([
            "id",
            "name",
            "thumbnail_link",
            "external_link",
            "description",
            "slug",
        ]);
    }

    public function scopeForNav(Builder $query)
    {
        return $query->select([
            "id",
            "name",
            "parent_id",
            "slug",
        ])
            ->whereNull("parent_id");
    }
    #endregion

    #region attributes
    protected function casts(): array
    {
        return [
            "banners" => "json",
            "product_form_field_amounts_enabled" => "boolean",
            "product_form_field_comment_enabled" => "boolean",
        ];
    }

    protected $appends = [
        "breadcrumbs",
        "depth",
        "name_for_list",
        "link",
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

    public function visible(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => collect(self::VISIBILITIES)->firstWhere(fn ($vv) => $vv["value"] == $v)["label"],
        );
    }

    protected function link(): Attribute
    {
        return Attribute::make(
            get: fn ($v, $attributes) => ($attributes["external_link"] ?? route("category", ["slug" => $attributes["slug"]])),
        );
    }

    public function getDepthAttribute(): int
    {
        return $this->parent_id ? $this->parent->depth + 1 : 0;
    }
    public function getTreeAttribute(): Collection
    {
        $cursor = $this;
        $tree = collect([$this]);
        while ($cursor->parent) {
            $cursor = $cursor->parent;
            $tree->prepend($cursor);
        }
        return $tree;
    }
    public function getBreadcrumbsAttribute(): string
    {
        return $this->tree
            ->map(fn ($category) => $category->name)
            ->implode(" » ");
    }
    public function getAllChildrenAttribute(): Collection
    {
        $cursor = $this;
        $all = collect([$this]);
        if ($cursor->children) {
            foreach ($cursor->children as $child) {
                $all->push($child);
                $all->push($child->all_children);
            }
        }
        return $all->flatten()->unique();
    }
    public function getNameForListAttribute(): string
    {
        return str_repeat("- ", $this->depth) . $this->name;
    }

    public function productFormFields(): Attribute
    {
        return Attribute::make(
            fn () => [
                "amounts" => [
                    "enabled" => $this->product_form_field_amounts_enabled ?? self::PRODUCT_FORM_FIELD_TEXTS_DEFAULTS["amounts"]["enabled"],
                    "label" => $this->product_form_field_amounts_label ?? self::PRODUCT_FORM_FIELD_TEXTS_DEFAULTS["amounts"]["label"],
                    "placeholder" => $this->product_form_field_amounts_placeholder ?? self::PRODUCT_FORM_FIELD_TEXTS_DEFAULTS["amounts"]["placeholder"],
                ],
                "comment" => [
                    "enabled" => $this->product_form_field_comment_enabled ?? self::PRODUCT_FORM_FIELD_TEXTS_DEFAULTS["comment"]["enabled"],
                    "label" => $this->product_form_field_comment_label ?? self::PRODUCT_FORM_FIELD_TEXTS_DEFAULTS["comment"]["label"],
                    "placeholder" => $this->product_form_field_comment_placeholder ?? self::PRODUCT_FORM_FIELD_TEXTS_DEFAULTS["comment"]["placeholder"],
                ],
            ],
        );
    }

    public function orderingNullsLast(): Attribute
    {
        return Attribute::make(
            get: fn ($v, $attributes) => $attributes["ordering"] ?? INF,
        );
    }
    #endregion

    #region relations
    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->as("categoryData")
            ->withPivot("ordering")
            ->where("visible", ">=", Auth::id() ? 1 : 2)
            ->orderByRaw("category_product.ordering is null")
            ->orderBy("category_product.ordering")
            ->orderBy("products.name")
            ->orderByRaw("products.price is null")
            ->orderBy("products.price");
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, "parent_id");
    }

    public function children()
    {
        return $this->hasMany(Category::class, "parent_id")->with("children")
            ->where("visible", ">=", Auth::id() ? 1 : 2)
            ->orderByRaw("case when ordering is null then 999 else ordering end")
            ->orderBy("name");
    }

    public function related()
    {
        return $this->belongsToMany(Category::class, "category_category_related", "host_category_id", "related_category_id");
    }
    #endregion

    #region helpers
    public const PRODUCT_FORM_FIELD_TEXTS_DEFAULTS = [
        "amounts" => [
            "enabled" => true,
            "label" => "Planowane ilości",
            "placeholder" => "np. 100/200/300 lub żółty:100 szt., zielony:50 szt.",
        ],
        "comment" => [
            "enabled" => true,
            "label" => "Komentarz do zapytania",
            "placeholder" => "np. dotyczący znakowania lub specyfikacji zapytania",
        ],
    ];
    #endregion
}
