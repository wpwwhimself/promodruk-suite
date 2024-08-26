<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        "visible",
        "name",
        "description",
        "color",
        "extra_description",
        "images",
        "thumbnails",
        "attributes",
        "original_sku",
        "price",
        "tabs",
    ];

    protected $casts = [
        "images" => "json",
        "thumbnails" => "json",
        "attributes" => "json",
        "color" => "json",
        "tabs" => "json",
    ];

    private function sortByName($first, $second)
    {
        return Str::beforeLast(Str::afterLast($first, "/"), ".") <=> Str::beforeLast(Str::afterLast($second, "/"), ".");
    }
    protected function images(): Attribute
    {
        return Attribute::make(fn ($value) => collect(json_decode($value))
            ->sort(fn ($a, $b) => $this->sortByName($a, $b))
            ->values()
        );
    }
    protected function thumbnails(): Attribute
    {
        return Attribute::make(fn ($value) => collect(json_decode($value))
            ->sortKeys()
            ->map(fn ($t, $i) => $t ?? $this->images[$i])
            ->sort(fn ($a, $b) => $this->sortByName($a, $b))
            ->values()
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
    public function getSimilarAttribute()
    {
        $data = collect();

        foreach ($this->categories as $category) {
            $data = $data->merge($category->products);
        }

        return $data;
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
