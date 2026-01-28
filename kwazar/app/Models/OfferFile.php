<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\ComponentAttributeBag;

class OfferFile extends Model
{
    use HasFactory;

    #region attributes
    public function file(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk("public")->get($this->file_path),
        );
    }
    #endregion

    public const META = [
        "label" => "Pliki oferty",
        "icon" => "file-lock",
        "description" => "",
        "role" => "offer-manager",
        "ordering" => 2,
        // "listScope" => "", // default scope to list items in model editor, empty defaults to forAdminList
        // "defaultSort" => "", // default sort, as it appears in url
        // "defaultFltr" => "", // default filters //todo expand
    ];

    protected $fillable = [
        'offer_id',
        'type',
        'file_path',
    ];

    #region presentation
    /**
     * Pretty display of a model - can use components and stuff
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Display for select options - text only
     */
    public function optionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name,
        );
    }

    /**
     * Pretty display for model tiles
     */
    public function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => view("components.shipyard.app.h", [
                "lvl" => 3,
                "icon" => $this->icon ?? self::META["icon"],
                "attributes" => new ComponentAttributeBag([
                    "role" => "card-title",
                ]),
                "slot" => $this,
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

    public function displayPreTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => null,
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
        //     // "default" => ,
        //     // "autofillFrom" => ["<route>", "<model_name>"],
        //     // "characterLimit" => 999, // for text fields
        //     // "role" => "",
        //     // "allowNulls" => true, // for when null values should not be treated as disabling filter but actual values
        // ],
    ];

    public const CONNECTIONS = [
        // "<name>" => [
        //     "model" => ,
        //     "mode" => "<one|many>",
        //     // "field_name" => "",
        //     // "field_label" => "",
        //     // "readonly" => true,
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

    /**
     * extended form validation on model save
     * set result to true if everything is ok, false with message to force back with toast
     */
    // public static function validateOnSave($data): array
    // {
    //     $res = [
    //         "result" => true/false,
    //         "message" => "",
    //     ];
    //
    //     // validation...
    //
    //     return $res;
    // }

    /**
     * extended form fields autofill on model save
     * add or update fields inside $data to trigger additional changes based on existing form data
     * then return updated $data
     */
    // public static function autofillOnSave(array $data): array
    // {
    //     return $data;
    // }
    #endregion

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
        //     "type" => "<input type>",
        //     "operator" => "regexp",
        //     "selectData" => [
        //     ],
        // ],
    ];

    public const EXTRA_SECTIONS = [
        // "<id>" => [
        //     "title" => "",
        //     "icon" => "",
        //     "show-on" => "<list|edit>",
        //     "component" => "<component_name>",
        //     "role" => "",
        // ],
    ];

    #region scopes
    use HasStandardScopes;

    public function scopePrepareQueue($query)
    {
        return $query->whereNull("file_path");
    }
    #endregion

    #region attributes
    protected function casts(): array
    {
        return [
            //
        ];
    }

    protected $appends = [

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

    //? override edit button on model list
    // public function modelEditButton(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn () => view("components.shipyard.ui.button", [
    //             "icon" => "pencil",
    //             "label" => "Edytuj",
    //             "action" => route(...),
    //         ])->render(),
    //     );
    // }
    #endregion

    #region relations
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
    #endregion

    #region helpers
    public const WORKER_DELAY_MINUTES = 5;
    #endregion
}
