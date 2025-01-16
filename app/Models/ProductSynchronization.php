<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        "last_sync_started_at", "last_sync_completed_at",
        "quickness_priority",
        "current_external_id",
        "progress",
        "synch_status",
    ];
    public $appends = [
        "status",
    ];

    protected $dates = [
        "last_sync_started_at",
        "last_sync_completed_at",
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

    public function status(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->synch_status !== null)
                ? self::STATUSES[$this->synch_status]
                : ["bd.", "ghost"],
        );
    }
    public function quicknessPriority(): Attribute
    {
        return Attribute::make(
            get: fn (int $priority) => self::QUICKNESS_LEVELS[$priority],
        );
    }
    public function lastSyncElapsedTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_sync_completed_at?->diffInSeconds($this->last_sync_started_at),
        );
    }
}
