<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSynchronization extends Model
{
    use HasFactory;

    protected $fillable = [
        "supplier_name",
        "product_import_enabled",
        "stock_import_enabled",
        "last_sync_started_at",
        "current_external_id",
        "progress",
    ];

    protected $dates = [
        "last_sync_started_at",
    ];
}
