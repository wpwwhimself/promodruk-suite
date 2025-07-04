<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

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
        "description",
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
    ];

    protected $casts = [
        "images" => "json",
        "thumbnails" => "json",
        "color" => "json",
        "sizes" => "json",
        "extra_filtrables" => "json",
        "tabs" => "json",
        "hide_family_sku_on_listing" => "boolean",
    ];

    public const CUSTOM_PRODUCT_GIVEAWAY = "@@";

    #region scopes
    public function scopeFamilyByPrefixedId(Builder $query, string $id): void
    {
        $query->where("front_id", "like", $id."%");
    }
    #endregion

    private function sortByName($first, $second)
    {
        return Str::beforeLast(Str::afterLast($first, "/"), ".") <=> Str::beforeLast(Str::afterLast($second, "/"), ".");
    }
    protected function images(): Attribute
    {
        return Attribute::make(fn ($value) => collect(json_decode($value))
            // ->sort(fn ($a, $b) => $this->sortByName($a, $b))
            ->values()
        );
    }
    protected function thumbnails(): Attribute
    {
        return Attribute::make(fn ($value) => collect($this->images)
            // ->sortKeys()
            ->map(fn ($img, $i) => json_decode($value)[$i] ?? $img)
            // ->sort(fn ($a, $b) => $this->sortByName($a, $b))
            ->values()
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

    protected $appends = [
        "family",
    ];

    public function getMagazynDataAttribute()
    {
        return Http::get(env("MAGAZYN_API_URL") . "products/" . $this->id)->collect();
    }
    public function getFamilyAttribute()
    {
        return Product::where("product_family_id", $this->product_family_id)->get();
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

    #region relations
    public function categories()
    {
        return $this->belongsToMany(Category::class)
            ->where("visible", ">=", Auth::id() ? 1 : 2);
    }

    public function tags()
    {
        return $this->belongsToMany(ProductTag::class, "product_product_tag", "product_family_id", "product_tag_id", "product_family_id", "id")
            ->as("details")
            ->withPivot("start_date", "end_date", "disabled");
    }
    #endregion
}
