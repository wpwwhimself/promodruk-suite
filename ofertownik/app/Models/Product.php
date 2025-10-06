<?php

namespace App\Models;

use Carbon\Carbon;
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
    ];

    protected $casts = [
        "specification" => "json",
        "images" => "collection",
        "thumbnails" => "json",
        "color" => "json",
        "sizes" => "json",
        "extra_filtrables" => "json",
        "tabs" => "json",
        "hide_family_sku_on_listing" => "boolean",
        "is_synced_with_magazyn" => "boolean",
    ];

    public const CUSTOM_PRODUCT_GIVEAWAY = "@@";

    #region scopes
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

    private function sortByName($first, $second)
    {
        return Str::beforeLast(Str::afterLast($first, "/"), ".") <=> Str::beforeLast(Str::afterLast($second, "/"), ".");
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

    #region relations
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
            ->withPivot("start_date", "end_date", "disabled")
            ->orderByDesc("gives_priority_on_listing")
            ->orderBy("start_date");
    }
    #endregion
}
