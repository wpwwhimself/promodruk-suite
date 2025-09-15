<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $primaryKey = "name";
    protected $keyType = "string";

    protected $fillable = [
        "name",
        "label",
        "group",
        "value",
    ];

    public const SHOWCASE_MODES = [
        "Tekst + film" => "film",
        "Tekst" => "text",
        "Karuzela zdjęć" => "carousel",
    ];

    public const SIDE_BANNER_MODES = [
        "Film" => "film",
        "Karuzela zdjęć" => "carousel",
    ];
}
