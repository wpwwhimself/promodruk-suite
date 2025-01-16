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
        "marking_import_enabled",
        "last_sync_started_at",
        "current_external_id",
        "progress",
        "synch_status",
        "quickness_priority",
    ];
    public $appends = [
        "status",
    ];

    protected $dates = [
        "last_sync_started_at",
    ];

    public const STATUSES = [
        0 => ["Czeka", "ghost"],
        1 => ["W toku", ""],
        2 => ["Błąd", "error"],
        3 => ["Sukces", "success"],
    ];

    public const QUICKNESS_LEVELS = [
        0 => "turbo",
        1 => "szybko",
        2 => "wolno",
        3 => "ślimaczo",
    ];

    public function getStatusAttribute(): array
    {
        return ($this->synch_status !== null)
            ? self::STATUSES[$this->synch_status]
            : ["bd.", "ghost"];
    }
}
