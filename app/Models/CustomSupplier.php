<?php

namespace App\Models;

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

    public function productFamilies()
    {
        return $this->hasMany(ProductFamily::class, "source", "name");
    }
}
