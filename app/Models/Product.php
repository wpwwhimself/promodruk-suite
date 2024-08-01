<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        "extra_description",
        "images",
        "attributes",
    ];

    protected $casts = [
        "images" => "json",
        "attributes" => "json",
    ];

    public function getMagazynDataAttribute()
    {
        return Http::get(env("MAGAZYN_API_URL") . "products/" . $this->id)->collect();
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
