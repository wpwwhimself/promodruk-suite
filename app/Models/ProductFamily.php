<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProductFamily extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id",
        "visible",
        "name",
        "description",
        "extra_description",
        "original_category",
        "images",
        "thumbnails",
        "tabs",
        "related_family_ids",
    ];

    protected $appends = [
        "images",
        "thumbnails",
    ];

    protected $casts = [
        "images" => "json",
        "thumbnails" => "json",
        "tabs" => "json",
    ];

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
    protected function anyThumbnail(): Attribute
    {
        return Attribute::make(fn () =>
            $this->thumbnails?->first()
            ?? ($this->products?->count()
                ? $this->products->random()->thumbnails?->first()
                : null
            )
        );
    }
    protected function variantsList(): Attribute
    {
        return Attribute::make(function () {
            $colors = $this->family->pluck("color")->unique();
            $sizes = $this->family->pluck("size_name")->unique();
            return compact("colors", "sizes");
        });
    }
    protected function similar(): Attribute
    {
        return Attribute::make(function () {
            $data = collect();

            foreach ($this->categories as $category) {
                $data = $data->merge($category->products);
            }

            return $data;
        });
    }
    protected function related(): Attribute
    {
        return Attribute::make(fn () => (empty($this->related_family_ids))
            ? collect([])
            : ProductFamily::whereIn("id", explode(";", $this->related_family_ids))
                ->orderBy("id")
                ->get()
        );
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)
            ->where("visible", ">=", Auth::id() ? 1 : 2);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
