<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        "last_sync_started_at", "last_sync_zero_at",
        "last_sync_completed_at", "last_sync_zero_to_full",
        "quickness_priority",
        "current_external_id",
        "progress", "module_in_progress",
        "synch_status",
    ];
    public $appends = [
        "status",
    ];

    protected $casts = [
        "last_sync_started_at" => "datetime",
        "last_sync_zero_at" => "datetime",
        "last_sync_completed_at" => "datetime",
    ];

    public const STATUSES = [
        0 => ["Czeka", "ghost"],
        1 => ["W toku", ""],
        2 => ["Błąd", "error"],
        3 => ["Sukces", "success"],
    ];

    public const MODULES = [
        "product" => ["🛒", "Produkty"],
        "stock" => ["📦", "Stany magazynowe"],
        "marking" => ["🎨", "Znakowania"],
    ];

    public const QUICKNESS_LEVELS = [
        0 => "turbo",
        1 => "szybko",
        2 => "wolno",
        3 => "ślimaczo",
    ];

    public const ENABLED_LEVELS = [
        0 => "wył.",
        1 => "1",
        2 => "2",
        3 => "3",
    ];

    #region scopes
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy("quickness_priority")
            ->orderBy("progress")
            ->orderBy("last_sync_started_at")
            ->orderBy("supplier_name");
    }
    #endregion

    #region attributes
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

    public function timestampSummary(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                "🟢" => $this->last_sync_started_at?->diffForHumans() . ($this->last_sync_started_at?->lte(now()->subDay()) ? " 🟥" : null),
                "⏱️" => $this->last_sync_zero_to_full ? CarbonInterval::seconds($this->last_sync_zero_to_full)->cascade()->format("%h:%I:%S") : null,
                "🛫" => $this->last_sync_zero_at?->diffForHumans() . ($this->last_sync_zero_at?->lte(now()->subDay()) ? " 🟥" : null),
                "🛬" => $this->last_sync_completed_at?->diffForHumans(),
            ],
        );
    }

    public function anythingEnabled(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->product_import_enabled || $this->stock_import_enabled || $this->marking_import_enabled,
        );
    }
    #endregion

    #region relations
    public function productFamilies()
    {
        return $this->hasMany(ProductFamily::class, "source", "supplier_name");
    }
    #endregion

    #region helpers
    public static function queue(): Collection
    {
        $queue = collect(
            DB::select(<<<SQL
                select
                    row_number() over (order by enabled, default_module_priority, quickness_priority, progress, last_sync_started_at, supplier_name) as queue_id,
                    x.*
                from (
                    select id, 'product' as "module", 2 as "default_module_priority", product_import_enabled as "enabled" from product_synchronizations ps
                    union
                    select id, 'stock' as "module", 1 as "default_module_priority", stock_import_enabled as "enabled" from product_synchronizations ps
                    union
                    select id, 'marking' as "module", 3 as "default_module_priority", marking_import_enabled as "enabled" from product_synchronizations ps
                ) x
                join product_synchronizations ps on ps.id = x.id
                where x.enabled <> 0
                order by queue_id;
            SQL)
        )
            ->map(fn ($q) => [
                "queue" => $q,
                "sync" => self::where("id", $q->id)->first()
            ]);

        return $queue;
    }

    /**
     * adds integration log and handles database to reflect that
     */
    public function addLog(
        string $status,
        int $depth,
        string $message,
        ?string $extra_info = null,
    ): void
    {
        /**
         * dictionary: status => [database status code, log level]
         */
        $dict = [
            "stopped" => [null, "info"],
            "pending" => [0, "info"],
            "pending (step)" => [0, "debug"],
            "pending (info)" => [0, "info"],
            "in progress" => [1, "debug"],
            "in progress (step)" => [1, "debug"],
            "error" => [2, "error"],
            "complete" => [3, "info"],
        ];

        //* add log
        Log::{$dict[$status][1]}("🧃 [{$this->supplier_name}] ".str_repeat("• ", $depth).$message);

        //* update database
        $new_status = empty($dict[$status][0]) ? [] : ["synch_status" => $dict[$status][0]];

        switch ($status) {
            case "pending":
                $new_status["last_sync_started_at"] = Carbon::now();
                $new_status["module_in_progress"] = $extra_info;
                $new_status["progress"] = 0;
                if (empty($this->last_sync_zero_at)) $new_status["last_sync_zero_at"] = Carbon::now();
                break;
            case "in progress":
                if ($extra_info) $new_status["current_external_id"] = $extra_info;
                break;
            case "in progress (step)":
                if ($extra_info) $new_status["progress"] = $extra_info;
                break;
            case "error":
                break;
            case "complete":
                $new_status["current_external_id"] = null;
                $new_status["last_sync_zero_at"] = null;
                $new_status["last_sync_completed_at"] = Carbon::now();
                $new_status["last_sync_zero_to_full"] = Carbon::now()->diffInSeconds($this->last_sync_zero_at);
                break;
        }

        $this->update($new_status);
    }
    #endregion
}
