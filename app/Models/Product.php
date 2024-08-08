<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id",
        "name",
        "description",
        "product_family_id",
        "original_category",
        "original_color_name",
        "image_urls",
        "thumbnail_urls",
    ];

    protected $appends = [
        "images",
        "thumbnails",
        "color",
    ];

    protected $casts = [
        "image_urls" => "json",
        "thumbnail_urls" => "json",
    ];

    public function getImagesAttribute()
    {
        return collect($this->image_urls)
            ->sort(fn ($a, $b) => Str::beforeLast($a, ".") <=> Str::beforeLast($b, "."))
            ->merge(
                collect(Storage::allFiles("public/products/$this->id"))
                    ->map(fn ($path) => env("APP_URL") . Storage::url($path))
            );
    }
    public function getThumbnailsAttribute()
    {
        return collect($this->thumbnail_urls)
            ->sort(fn ($a, $b) => Str::beforeLast($a, ".") <=> Str::beforeLast($b, "."));
    }
    public function getColorAttribute()
    {
        $invalid = (object) collect([
            "name" => $this->original_color_name,
            "color" => null,
            "description" => "*brak podglądu*"
        ])
            ->all();
        return (!empty($this->original_color_name))
            ? MainAttribute::where("name", "like", "%$this->original_color_name%")->first() ?? $invalid
            : $invalid;
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class);
    }
    public function mainAttribute()
    {
        return $this->belongsTo(MainAttribute::class);
    }
    public function stock()
    {
        return $this->hasOne(Stock::class, "id");
    }
}
