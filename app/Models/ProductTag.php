<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTag extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "type",
        "ribbon_color",
        "ribbon_text",
        "ribbon_text_size_pt",
        "ribbon_text_color",
        "gives_priority_on_listing",
    ];

    public const TYPES = [
        "top-bar" => "u góry",
        "right-corner" => "w prawym rogu",
    ];

    protected $casts = [
        "gives_priority_on_listing" => "boolean",
    ];

    public function __toString()
    {
        return $this->name . ($this->gives_priority_on_listing ? " 📌" : "");
    }

    #region scopes
    public function scopeOrdered(Builder $query)
    {
        return $query->orderBy("name");
    }
    #endregion

    #region relations
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
    #endregion
}
