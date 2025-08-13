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
    ];

    protected $appends = [
        "breadcrumbs",
        "depth",
        "name_for_list",
        "link",
    ];

    protected $casts = [
        "banners" => "json",
    ];

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
            ->orderBy("ordering")->orderBy("name");
    }
    #endregion
}
