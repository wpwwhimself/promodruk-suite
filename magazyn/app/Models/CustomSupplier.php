<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
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
    public function productFamilies(): Attribute // not really a relation, I know
    {
        return Attribute::make(
            get: fn () => ProductFamily::where("source", ProductFamily::CUSTOM_PRODUCT_GIVEAWAY.$this->id)->get(),
        );
    }
    #endregion
}
