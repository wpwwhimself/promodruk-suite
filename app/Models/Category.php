<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = "categories";

    protected $fillable = [
        "name", "label", "description",
        "thumbnail_link", "external_link",
        "visible", "ordering", "parent_id",
    ];

    public function getDepthAttribute(): int
    {
        return ($this->parent_id) ? $this->parent->depth + 1 : 0;
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function parent()
    {
        return $this->belongsTo(Category::class, "parent_id");
    }
}
