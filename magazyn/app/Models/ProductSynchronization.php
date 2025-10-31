<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\ComponentAttributeBag;

class ProductSynchronization extends Model
{
    use HasFactory;

    public const META = [
        "label" => "Synchronizacje",
        "icon" => "cloud",
        "description" => "Dane pochodzące ze źródeł danych od dostawców.",
        "role" => "",
        "ordering" => 19,
    ];

    protected $primaryKey = "supplier_name";
    protected $keyType = "string";

    protected $fillable = [
        "supplier_name",
        "product_import_enabled", "stock_import_enabled", "marking_import_enabled",
        "quickness_priority",
        "module_in_progress",
        "product_import", "stock_import", "marking_import",
    ];

    #region presentation
    public function __toString(): string
    {
        return $this->supplier_name;
    }

    public function optionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->supplier_name,
        );
    }

    public function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.shipyard.app.h", [
                "lvl" => 3,
                "icon" => $this->icon ?? self::META["icon"],
                "attributes" => new ComponentAttributeBag([
                    "role" => "card-title",
                ]),
                "slot" => $this->supplier_name,
            ])->render(),
        );
    }

    public function displaySubtitle(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.shipyard.app.model.badges", [
                "badges" => $this->badges,
            ])->render(),
        );
    }

    public function displayMiddlePart(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.shipyard.app.model.connections-preview", [
                "connections" => self::getConnections(),
                "model" => $this,
            ])->render(),
        );
    }
    #endregion

    #region fields
    use HasStandardFields;

    public const FIELDS = [
        // "<column_name>" => [
        //     "type" => "<input_type>",
        //     "columnTypes" => [ // for JSON
        //         "<label>" => "<input_type>",
        //     ],
        //     "selectData" => [ // for select
        //         "options" => ["label" => "", "value" => ""],
        //         "emptyOption" => "",
        //     ],
        //     "label" => "",
        //     "hint" => "",
        //     "icon" => "",
        //     // "required" => true,
        //     // "autofillFrom" => ["<route>", "<model_name>"],
        //     // "characterLimit" => 999, // for text fields
        //     // "hideForEntmgr" => true,
        //     // "role" => "",
        // ],
    ];

    public const CONNECTIONS = [
        // "<name>" => [
        //     "model" => ,
        //     "mode" => "<one|many>",
        //     // "field_name" => "",
        //     // "field_label" => "",
        // ],
    ];

    public const ACTIONS = [
        // [
        //     "icon" => "",
        //     "label" => "",
        //     "show-on" => "<list|edit>",
        //     "route" => "",
        //     "role" => "",
        //     "dangerous" => true,
        // ],
    ];
    #endregion

    // use CanBeSorted;
    public const SORTS = [
        // "<name>" => [
        //     "label" => "",
        //     "compare-using" => "function|field",
        //     "discr" => "<function_name|field_name>",
        // ],
    ];

    public const FILTERS = [
        // "<name>" => [
        //     "label" => "",
        //     "icon" => "",
        //     "compare-using" => "function|field",
        //     "discr" => "<function_name|field_name>",
        //     "mode" => "<one|many>",
        //     "operator" => "",
        //     "options" => [
        //         "<label>" => <value>,
        //     ],
        // ],
    ];

    #region scopes
    use HasStandardScopes;

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy("quickness_priority")
            ->orderBy("supplier_name");
    }
    #endregion

    #region attributes
    protected $casts = [
        "product_import" => "collection",
        "stock_import" => "collection",
        "marking_import" => "collection",
    ];

    public $appends = [
        "status",
    ];

    use HasStandardAttributes;

    // public function badges(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn () => [
    //             [
    //                 "label" => "",
    //                 "icon" => "",
    //                 "class" => "",
    //                 "style" => "",
    //                 "condition" => "",
    //             ],
    //             [
    //                 "html" => "",
    //             ],
    //         ],
    //     );
    // }

    public function status(): Attribute
    {
        try {
            if (!$this->module_in_progress) throw new \Exception("No module in progress");

            return Attribute::make(
                get: fn () => self::STATUSES[$this->{$this->module_in_progress."_import"}->get("synch_status") ?? -1],
            );
        } catch (\Exception $e) {
            return Attribute::make(
                get: fn () => ["bd.", "ghost"],
            );
        }
    }

    public function currentModuleData(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->{$this->module_in_progress."_import"},
        );
    }

    public function quicknessPriorityNamed(): Attribute
    {
        return Attribute::make(
            get: fn (int $priority) => self::QUICKNESS_LEVELS[$priority],
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
    public const STATUSES = [
        -1 => ["bd.", "ghost"],
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

    public function timestampSummary(string $module): array
    {
        $started_at = $this->{$module."_import"}?->get("last_sync_started_at");
        if ($started_at) $started_at = Carbon::parse($started_at);
        $zero_at = $this->{$module."_import"}?->get("last_sync_zero_at");
        if ($zero_at) $zero_at = Carbon::parse($zero_at);
        $completed_at = $this->{$module."_import"}?->get("last_sync_completed_at");
        if ($completed_at) $completed_at = Carbon::parse($completed_at);
        $zero_to_full = $this->{$module."_import"}?->get("last_sync_zero_to_full");
        if ($zero_to_full) $zero_to_full = CarbonInterval::seconds($zero_to_full)->cascade()->format("%h:%I:%S");

        return [
            ["icon" => "🟢", "value" => $started_at?->diffForHumans(), "class" => $started_at?->lte(now()->subDays(3)) ? "danger" : ($started_at?->gte(now()->subMinute()) ? "success" : null)],
            ["icon" => "⏱️", "value" => $zero_to_full],
            ["icon" => "🛫", "value" => $zero_at?->diffForHumans(), "class" => $zero_at?->lte(now()->subDays(3)) ? "danger" : ($zero_at?->gte(now()->subMinute()) ? "success" : null)],
            ["icon" => "🛬", "value" => $completed_at?->diffForHumans()],
        ];
    }

    public static function queue(): Collection
    {
        $queue = collect(
            DB::select(<<<SQL
                select
                    row_number() over (order by enabled, default_module_priority, quickness_priority, supplier_name) as queue_id,
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
        if ($status == "pending") $this->update(["module_in_progress" => $extra_info]);
        $module = $this->module_in_progress;
        $current_status = $this->{$module."_import"};

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
        Log::{$dict[$status][1]}(
            "🧃 [{$this->supplier_name}] "
            .($module ? self::MODULES[$module][0] . " " : "")
            .str_repeat("• ", $depth)
            .$message
        );

        //* update database
        $new_status = empty($dict[$status][0]) ? [] : ["synch_status" => $dict[$status][0]];

        switch ($status) {
            case "pending":
                $new_status["last_sync_started_at"] = Carbon::now();
                $new_status["progress"] = 0;
                if (empty($current_status["last_sync_zero_at"]) || $current_status["synch_status"] == $dict["complete"][0]) $new_status["last_sync_zero_at"] = Carbon::now();
                break;
            case "in progress":
                if ($extra_info) $new_status["current_external_id"] = $extra_info;
                break;
            case "in progress (step)":
                if ($extra_info) $new_status["progress"] = round($extra_info, 2);
                break;
            case "error":
                break;
            case "complete":
                $new_status["current_external_id"] = null;
                $new_status["last_sync_completed_at"] = Carbon::now();
                $new_status["last_sync_zero_to_full"] = Carbon::now()->diffInSeconds($current_status["last_sync_zero_at"]);
                break;
        }

        $this->update([$module."_import" => collect($this->{$module."_import"} ?? [])->merge($new_status)]);
    }
    #endregion
}
