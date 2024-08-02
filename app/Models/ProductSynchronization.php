<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSynchronization extends Model
{
    use HasFactory;

    protected $fillable = [
        "supplier_name",
        "enabled",
        "last_synch_started_at",
    ];

    protected $dates = [
        "last_synch_started_at",
    ];
}
