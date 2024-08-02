<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id",
        "current_stock",
        "future_delivery_amount",
        "future_delivery_date",
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, "id");
    }
}
