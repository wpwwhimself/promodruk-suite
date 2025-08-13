<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MainAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "color",
        "primary_color_id",
    ];

    public static function invalidColor()
    {
        return (object) collect([
            "name" => "*brak informacji*",
            "color" => null,
            "description" => "*brak podglądu*",
            "id" => "?",
        ])->all();
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
        if (Str::startsWith($relatedColor?->color, "@")) return $this->final_color;

        return $relatedColor;
    }

    public function getRelatedColorsAttribute()
    {
        return MainAttribute::where("color", "@".$this->id)->get();
    }

    public function primaryColor()
    {
        return $this->belongsTo(PrimaryColor::class);
    }
    #endregion
}
