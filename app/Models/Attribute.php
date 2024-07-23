<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "type",
    ];

    public static $types = [
        "tekstowy" => "text",
        "liczbowy" => "number",
        "kolor" => "color",
    ];

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
}
