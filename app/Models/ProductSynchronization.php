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
        "synch_status",
    ];

    protected $dates = [
        "last_sync_started_at",
    ];

    public const STATUSES = [
        0 => ["Nierozpoczęta", "ghost"],
        1 => ["W toku", ""],
        2 => ["Przerwana błędem", "error"],
        3 => ["Zakończona sukcesem", "success"],
    ];

    public function getStatusAttribute(): array
    {
        return ($this->synch_status !== null)
            ? self::STATUSES[$this->synch_status]
            : ["bd.", "ghost"];
    }
}
