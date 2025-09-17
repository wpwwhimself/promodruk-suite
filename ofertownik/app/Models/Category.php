<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    use HasFactory;

    protected $table = "categories";

    protected $fillable = [
        "name", "label", "description", "welcome_text",
        "thumbnail_link", "external_link",
        "visible", "ordering", "parent_id",
        "banners",
        "product_form_field_amounts_enabled", "product_form_field_amounts_label", "product_form_field_amounts_placeholder", "product_form_field_comment_enabled", "product_form_field_comment_label", "product_form_field_comment_placeholder",
    ];

    protected $appends = [
        "breadcrumbs",
        "depth",
        "name_for_list",
        "link",
    ];

    protected $casts = [
        "banners" => "json",
        "product_form_field_amounts_enabled" => "boolean",
        "product_form_field_comment_enabled" => "boolean",
    ];

    #region scopes
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
        ]);
    }

    public function scopeForNav(Builder $query)
    {
        return $query->select([
            "id",
            "name",
            "parent_id",
        ])
            ->whereNull("parent_id");
    }
    #endregion

    protected function link(): Attribute
    {
        return Attribute::make(
            get: fn ($v, $attributes) => ($attributes["external_link"] ?? route("category-$attributes[id]")),
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
