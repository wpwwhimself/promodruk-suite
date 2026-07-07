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

class Domain extends Model implements ContractsAuditable
{
    //

    public const META = [
        "label" => "Domeny",
        "icon" => "drama-masks",
        "description" => "Domeny pozwalają na wyświetlanie różnych odsłon Ofertownika w zależności od adresu, po którym się na niego wchodzi.",
        "role" => "technical",
        "ordering" => 21,
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
            get: fn () => $this->domain,
        );
    }

    public function displayPreTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => "<img class='logo'
                src='$this->favicon_url'
                alt='favicon'
            />",
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
    public const FIELDS = [
        "domain" => [
            "type" => "url",
            "label" => "Adres główny",
            "icon" => "link",
            "required" => true,
        ],
        "primary_color" => [
            "type" => "color",
            "label" => "Kolor podstawowy",
            "icon" => "palette",
            "required" => true,
            "hint" => "Kolor akcentów i zwykłych przycisków.",
            "default" => "#d9ca80",
        ],
        "secondary_color" => [
            "type" => "color",
            "label" => "Kolor drugorzędny",
            "icon" => "palette",
            "required" => true,
            "hint" => "Kolor aktywnych i najechanych elementów, w tym przycisków.",
            "default" => "#85ca56"
        ],
        "tertiary_color" => [
            "type" => "color",
            "label" => "Kolor trzeciorzędny",
            "icon" => "palette",
            "required" => true,
            "hint" => "Kolor lekkiego tła, zazwyczaj jasny. Widoczny np. w nagłówku.",
            "default" => "#e6e6e6",
        ],
        "logo_url" => [
            "type" => "url-storage",
            "label" => "Własne logo",
            "icon" => "image",
        ],
        "favicon_url" => [
            "type" => "url-storage",
            "label" => "Własny favicon",
            "icon" => "image-text",
        ],
        "is_active" => [
            "type" => "checkbox",
            "label" => "Aktywny",
            "icon" => "cog",
        ],
    ];

    protected $fillable = [
        "name",
        "domain",
        "primary_color", "secondary_color", "tertiary_color",
        "logo_url", "favicon_url",
        "is_active",
    ];
    #endregion

    #region relations
    public const CONNECTIONS = [
        "pages" => [
            "model" => DomainPage::class,
            "mode" => "many",
        ],
        "supervisors" => [
            "model" => Supervisor::class,
            "mode" => "many",
        ],
    ];

    public function pages()
    {
        return $this->belongsToMany(DomainPage::class);
    }

    public function supervisors()
    {
        return $this->belongsToMany(Supervisor::class);
    }
    #endregion

    #region actions and extras
    #endregion

    #region scopes
    #endregion

    #region sorts and filters
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

    public static function getStyleDataByDomain(string $domain): array
    {
        if (Str::contains($domain, ["localhost", "www.ofertownik.promovera.pl"])) {
            return [];
        }

        $domain = self::where("domain", $domain)->firstOrFail();

        return [
            "name" => $domain->name,
            "primary_color" => $domain->primary_color,
            "secondary_color" => $domain->secondary_color,
            "tertiary_color" => $domain->tertiary_color,
            "logo" => $domain->logo_url,
            "favicon" => $domain->favicon_url,
        ];
    }
    #endregion

    #region on-saves
    public static function autofillOnSave(array $data): array
    {
        // domain must not end with slash
        $data["domain"] = Str::charAt($data["domain"], -1) == "/"
            ? Str::beforeLast($data["domain"], "/")
            : $data["domain"];

        return $data;
    }
    #endregion
}
