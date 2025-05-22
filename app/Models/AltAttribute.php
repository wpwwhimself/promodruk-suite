<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AltAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'variants',
        'large_tiles',
    ];

    protected $casts = [
        "variants" => "json",
        "large_tiles" => "boolean",
    ];

    #region attributes
    public function variantNames(): Attribute
    {
        return Attribute::make(
            get: fn () => array_keys($this->variants),
        );
    }
    #endregion

    #region helpers
    public function forTile(?string $variant_name): array
    {
        if (!$variant_name) {
            return [
                "selected" => [
                    "label" => "brak informacji",
                    "img" => null,
                ],
                "data" => $this,
            ];
        }

        $selected = collect($this->variants)
            ->filter(fn ($img, $lbl) => $lbl == $variant_name);

        return [
            "selected" => [
                "label" => $selected->keys()->first(),
                "img" => $selected->first(),
            ],
            "data" => $this,
        ];
    }

    public function allVariantsForTiles(): Collection
    {
        return collect($this->variants)
            ->map(fn ($img, $lbl) => $this->forTile($lbl));
    }
    #endregion
}
