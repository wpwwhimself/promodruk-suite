<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductFamily extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id",
        "original_sku",
        "name",
        "subtitle",
        "description",
        "description_label",
        "source",
        "original_category",
        "image_urls",
        "thumbnail_urls",
        "tabs",
        "alt_attributes",
    ];

    protected $appends = [
        "images",
        "thumbnails",
        "prefixed_id",
    ];

    protected $casts = [
        "image_urls" => "json",
        "thumbnail_urls" => "json",
        "tabs" => "json",
        "alt_attributes" => "json",
    ];

    public const CUSTOM_PRODUCT_GIVEAWAY = "@@";

    #region attributes
    public function getImagesAttribute()
    {
        return collect($this->image_urls)
            ->sortBy(0)
            ->mapWithKeys(fn ($img) => [$img[0] => $img[1]])
            // ->merge(
            //     collect(Storage::allFiles("public/products/$this->id/images"))
            //         ->map(fn ($path) => env("APP_URL") . Storage::url($path))
            // )
        ;
    }
    public function getThumbnailsAttribute()
    {
        return collect($this->thumbnail_urls)
            ->sortKeys()
            ->sort(fn ($a, $b) => Str::beforeLast($a, ".") <=> Str::beforeLast($b, "."))
            // ->merge(
            //     collect(Storage::allFiles("public/products/$this->id/thumbnails"))
            //         ->map(fn ($path) => env("APP_URL") . Storage::url($path))
            // )
        ;
    }
    public function getAnyThumbnailAttribute()
    {
        return $this->thumbnails?->first()
            ?? ($this->products?->count()
                ? $this->products->random()->thumbnails?->first()
                : null
            );
    }
    public function getIsCustomAttribute()
    {
        return Str::startsWith($this->id, self::CUSTOM_PRODUCT_GIVEAWAY);
    }
    public function getSupplierAttribute()
    {
        return $this->is_custom
            ? CustomSupplier::find(Str::after($this->source, self::CUSTOM_PRODUCT_GIVEAWAY))
            : ProductSynchronization::where("supplier_name", $this->source)->first();
    }
    public function getPrefixedIdAttribute()
    {
        return $this->is_custom
            ? Str::replace(self::CUSTOM_PRODUCT_GIVEAWAY, $this->supplier->prefix, $this->id)
            : $this->id;
    }

    public function getAltAttributeTilesAttribute()
    {
        if (!$this->alt_attributes) return [];
        return collect($this->alt_attributes["variants"])
            ->map(fn ($img, $lbl) => $this->attributeForTile($lbl));
    }
    public function getAltAttributeVariantsAttribute()
    {
        if (!$this->alt_attributes) return [];
        return array_keys($this->alt_attributes["variants"]);
    }
    #endregion

    #region relations
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    #endregion

    #region helpers
    public static function newCustomProductId(): string
    {
        do {
            $random_number = Str::of(rand(0, 999999))->padLeft(6, "0");
            $id = self::CUSTOM_PRODUCT_GIVEAWAY . $random_number;
        } while (ProductFamily::where("id", $id)->exists());

        return $id;
    }

    public static function getByPrefixedId(string $prefixed_id): ProductFamily
    {
        $main_part = preg_match("/\d{6}/", $prefixed_id, $matches) ? $matches[0] : null;
        return ProductFamily::findOrFail(self::CUSTOM_PRODUCT_GIVEAWAY . $main_part);
    }

    public function attributeForTile(?string $variant_name): array
    {
        $ret = [
            "selected" => [
                "label" => "brak informacji",
                "img" => null,
            ],
            "data" => $this->alt_attributes,
        ];

        if (!$variant_name) return $ret;

        $selected = collect($this->alt_attributes["variants"])
            ->filter(fn ($img, $lbl) => $lbl == $variant_name);

        $ret["selected"]["label"] = $selected->keys()->first() ?? $variant_name;
        $ret["selected"]["img"] = $selected->first();

        return $ret;
    }
    #endregion
}
