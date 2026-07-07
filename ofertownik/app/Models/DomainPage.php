<?php

namespace App\Models;

use App\Traits\Shipyard\HasStandardAttributes;
use App\Traits\Shipyard\HasStandardFields;
use App\Traits\Shipyard\HasStandardScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\Support\Str;
use Mattiverse\Userstamps\Traits\Userstamps;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;

class DomainPage extends Model implements ContractsAuditable
{
    //

    public const META = [
        "label" => "Podstrony domen",
        "icon" => "script-text-outline",
        "description" => "Pozycje menu wyświetlane dla domen. Podstrony dla głównej domeny definiowane są w osobnym menu 'Podstrony'.",
        "role" => "content-manager",
        "ordering" => 22,
        "defaultSort" => "order",
    ];

    use HasStandardFields, HasStandardScopes, HasStandardAttributes;
    use SoftDeletes, Userstamps, Auditable;

    #region presentation
    public function __toString(): string
    {
        return $this->name;
    }

    public function optionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name,
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
            get: fn () => view("components.shipyard.app.model.fields-preview", [
                "model" => $this,
                "fields" => ["visible", "order",],
            ])->render()
                . view("components.shipyard.ui.button", [
                    "action" => route("standard-page", ["slug" => $this->slug]),
                    "icon" => "eye",
                    "pop" => "Przejdź do strony",
                    "attributes" => new ComponentAttributeBag([
                        "target" => "_blank",
                    ]),
                ])->render(),
        );
    }
    #endregion

    #region fields
    public const FIELDS = [
        "content" => [
            "type" => "HTML",
            "label" => "Treść",
            "icon" => "pencil",
        ],
    ];

    protected $fillable = [
        "name", "content",
        "visible", "order",
    ];
    #endregion

    #region relations
    public const CONNECTIONS = [
        // "<name>" => [
        //     "model" => ,
        //     "mode" => "<one|many|many-reverse>",
        // ],
    ];

    //? tutaj dodaj metody od relacji ?//
    #endregion

    #region actions and extras
    public const ACTIONS = [
        [
            "icon" => "eye",
            "label" => "Przejdź do strony",
            "show-on" => "edit",
            "route" => "domain-page",
            "params" => ["slug" => "slug"],
            // "role" => "",
            // "dangerous" => true,
        ],
    ];
    #endregion

    #region scopes
    #endregion

    #region sorts and filters
    public const SORTS = [
        "order" => [
            "label" => "Kolejność",
            "compare-using" => "field",
            "discr" => "order",
        ],
        "name" => [
            "label" => "Nazwa",
            "compare-using" => "field",
            "discr" => "name",
        ],
    ];

    public const FILTERS = [
        "visible" => [
            "label" => "Widoczność",
            "compare-using" => "field",
            "discr" => "visible",
            "type" => "select",
            "operator" => "=",
            "selectData" => [
                "options" => [
                    ["label" => "Dla wszystkich", "value" => 2],
                    ["label" => "Dla zalogowanych", "value" => 1],
                    ["label" => "Nie", "value" => 0],
                ],
                "emptyOption" => "Wszystkie",
            ],
        ],
        // "domain" => [
        //     "label" => "Domena",
        //     "compare-using" => "function",
        //     ...
        // ],
        //todo do rozwinięcia
    ];
    #endregion

    #region attributes and helpers
    protected function casts(): array
    {
        return [
            //
        ];
    }

    protected $appends = [

    ];

    public function slug(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::slug($this->name),
        );
    }
    #endregion

    #region on-saves
    #endregion
}
