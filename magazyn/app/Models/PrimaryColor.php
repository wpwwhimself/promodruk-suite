<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrimaryColor extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "color",
        "description",
    ];

    public const COLOR_MODES = [
        "pojedynczy" => "single",
        "podwójny" => "double",
        "potrójny" => "triple",
        "wiele" => "multi",
    ];

    public function getColorModeAttribute()
    {
        foreach ([
            "wiele" => $this->color == "multi",
            "pojedynczy" => Str::substrCount($this->color, "#") == 1,
            "podwójny" => Str::substrCount($this->color, "#") == 2,
            "potrójny" => Str::substrCount($this->color, "#") == 3,
        ] as $result => $case) {
            if ($case) {
                return self::COLOR_MODES[$result];
            }
        }

        throw new \Exception("Unknown color mode");
    }

    public function attribute()
    {
        return $this->hasMany(MainAttribute::class);
    }
}
