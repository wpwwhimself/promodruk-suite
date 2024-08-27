<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
    ];

    protected $appends = [
        "breadcrumbs",
        "depth",
        "name_for_list",
    ];

    public const VISIBILITIES = [
        "Ukryta" => 0,
        "Prywatna" => 1,
        "Publiczna" => 2,
    ];

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

    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->where("visible", ">=", Auth::id() ? 1 : 2);
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
}
