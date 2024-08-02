<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = [
        "attribute_id",
        "name",
        "value",
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
