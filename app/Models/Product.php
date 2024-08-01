<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id",
        "name",
        "description",
        "main_attribute_id",
        "product_family_id",
    ];

    protected $appends = ["images"];

    public function getImagesAttribute()
    {
        return collect(Storage::allFiles("public/products/$this->id"))
            ->map(fn ($path) => env("APP_URL") . Storage::url($path));
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class);
    }
    public function mainAttribute()
    {
        return $this->belongsTo(MainAttribute::class);
    }
}
