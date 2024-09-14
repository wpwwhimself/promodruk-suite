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
    ];

    public function getColorModeAttribute()
    {
        return $this->color == "" ? self::COLOR_MODES["brak"] : (
            $this->color == "multi" ? self::COLOR_MODES["wiele"] : (
            Str::substrCount($this->color, "#") == 1 ? self::COLOR_MODES["pojedynczy"] : (
            Str::substrCount($this->color, "#") == 2 ? self::COLOR_MODES["podwójny"] :
            null
        )));
    }
}
