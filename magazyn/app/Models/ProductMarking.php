<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductMarking extends Model
{
    use HasFactory;

    protected $fillable = [
        "product_id",
        "position", "technique",
        "print_size",
        "images",
        "main_price_modifiers",
        "quantity_prices",
        "setup_price",
        "enable_discount",
    ];

    protected $casts = [
        "images" => "array",
        "main_price_modifiers" => "array",
        "quantity_prices" => "array",
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
