<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomSupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prefix',
        'notes',
        'categories',
    ];

    protected $casts = [
        'categories' => 'array',
    ];

    #region scopes
    public function scopePrefixes(Builder $query)
    {
        return $query->get()->pluck("prefix");
    }
    #endregion

    #region relations
    public function productFamilies()
    {
        return $this->hasMany(ProductFamily::class, "source", "name");
    }
    #endregion
}
