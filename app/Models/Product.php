<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

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
    ];

    protected $casts = [
        "images" => "json",
        "thumbnails" => "json",
        "attributes" => "json",
        "color" => "json",
    ];

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

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
