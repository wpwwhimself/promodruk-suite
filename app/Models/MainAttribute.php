<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MainAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "color",
        "description",
    ];

    public const COLOR_MODES = [
        "brak" => "none",
        "pojedynczy" => "single",
        "podwójny" => "double",
        "wiele" => "multi",
        "podrzędny do" => "related",
    ];

    public function getColorModeAttribute()
    {
        foreach ([
            "brak" => $this->color == "",
            "podrzędny do" => Str::startsWith($this->color, "@"),
            "wiele" => $this->color == "multi",
            "pojedynczy" => Str::substrCount($this->color, "#") == 1,
            "podwójny" => Str::substrCount($this->color, "#") == 2,
        ] as $result => $case) {
            if ($case) {
                return self::COLOR_MODES[$result];
            }
        }

        throw new \Exception("Unknown color mode");
    }

    #region color grouping
    public function getIsFinalAttribute()
    {
        return !Str::startsWith($this->color, "@");
    }
    public function getFinalColorAttribute()
    {
        if (!Str::startsWith($this->color, "@")) return $this;
        $relatedColor = MainAttribute::find(Str::after($this->color, "@"));

        return $relatedColor;
    }

    public function getRelatedColorsAttribute()
    {
        return MainAttribute::where("color", "@".$this->id)->get();
    }
    #endregion
}
