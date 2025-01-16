<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSynchronization extends Model
{
    use HasFactory;

    protected $primaryKey = "supplier_name";
    protected $keyType = "string";

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

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy("quickness_priority")
            ->orderBy("progress")
            ->orderBy("last_sync_started_at")
            ->orderBy("supplier_name");
    }

    public function status(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->synch_status !== null)
                ? self::STATUSES[$this->synch_status]
                : ["bd.", "ghost"],
        );
    }
    public function quicknessPriorityNamed(): Attribute
    {
        return Attribute::make(
            get: fn (int $priority) => self::QUICKNESS_LEVELS[$priority],
        );
    }
    public function lastSyncElapsedTime(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->last_sync_completed_at)
                ? Carbon::parse($this->last_sync_completed_at)?->diffInSeconds($this->last_sync_started_at)
                : null,
        );
    }
}
