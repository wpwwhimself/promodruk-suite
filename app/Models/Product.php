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
        "name",
        "description",
        "color",
        "size_name",
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
            ->merge($this->family->images)
            // ->sort(fn ($a, $b) => $this->sortByName($a, $b))
            ->values()
        );
    }
    protected function thumbnails(): Attribute
    {
        return Attribute::make(fn ($value) => collect($this->images)
            // ->sortKeys()
            ->map(fn ($img, $i) => json_decode($value)[$i] ?? $img)
            ->merge($this->family->thumbnails)
            // ->sort(fn ($a, $b) => $this->sortByName($a, $b))
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

    public function family()
    {
        return $this->belongsTo(ProductFamily::class);
    }
}
