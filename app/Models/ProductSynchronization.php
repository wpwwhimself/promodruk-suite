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
        "last_sync_started_at",
        "progress",
    ];

    protected $dates = [
        "last_sync_started_at",
    ];
}
