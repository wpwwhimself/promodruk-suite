<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id",
        "name",
        "description",
        "product_family_id",
        "original_sku",
        "original_color_name",
        "sizes",
        "image_urls",
        "thumbnail_urls",
        "price",
        "manipulation_cost",
        "tabs",
        "enable_discount",
        "import_id",
    ];

    protected $appends = [
        "images",
        "thumbnails",
        "color",
        "front_id",
    ];

    protected $casts = [
        "image_urls" => "json",
        "thumbnail_urls" => "json",
        "tabs" => "json",
        "sizes" => "json",
    ];

    public const CUSTOM_PRODUCT_VARIANT_SUFFIX_SEPARATOR = "-";

    public function getImagesAttribute()
    {
        return collect($this->image_urls)
            ->sort(fn ($a, $b) => Str::beforeLast($a, ".") <=> Str::beforeLast($b, "."))
            ->merge(
                collect(Storage::allFiles("public/products/$this->id/images"))
                    ->map(fn ($path) => env("APP_URL") . Storage::url($path))
            );
    }
    public function getThumbnailsAttribute()
    {
        return collect($this->thumbnail_urls)
            ->sort(fn ($a, $b) => Str::beforeLast($a, ".") <=> Str::beforeLast($b, "."))
            ->merge(
                collect(Storage::allFiles("public/products/$this->id/thumbnails"))
                    ->map(fn ($path) => env("APP_URL") . Storage::url($path))
            );
    }
    public function getColorAttribute()
    {
        $primary_color = PrimaryColor::where("name", $this->original_color_name)->first();
        $original_color = MainAttribute::where("name", "like", "%$this->original_color_name%")->first();

        foreach ([
            [empty($this->original_color_name), MainAttribute::invalidColor()],
            [$primary_color, $primary_color],
            [$original_color, $original_color->primaryColor ?? $original_color],
        ] as [$case, $ret]) {
            if ($case) return $ret;
        }

        return MainAttribute::invalidColor();
    }

    public function getIdSuffixAttribute()
    {
        return Str::after($this->id, $this->product_family_id);
    }

    public function getIsCustomAttribute()
    {
        return $this->productFamily->is_custom ?? null;
    }
    public function getSupplierAttribute()
    {
        return $this->productFamily->supplier ?? null;
    }
    public function getIsOnlyVariantAttribute()
    {
        return Product::where("product_family_id", $this->product_family_id)->count() == 1;
    }
    public function getFrontIdAttribute()
    {
        return $this->is_custom
            ? ($this->is_only_variant
                ? Str::of($this->id)->replace(ProductFamily::CUSTOM_PRODUCT_GIVEAWAY, $this->supplier->prefix)->before(self::CUSTOM_PRODUCT_VARIANT_SUFFIX_SEPARATOR)
                : Str::replace(ProductFamily::CUSTOM_PRODUCT_GIVEAWAY, $this->supplier->prefix, $this->id)
            )
            : $this->id;
    }

    public function productFamily()
    {
        return $this->belongsTo(ProductFamily::class);
    }
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class);
    }
    public function stock()
    {
        return $this->hasOne(Stock::class, "id");
    }
    public function markings()
    {
        return $this->hasMany(ProductMarking::class);
    }

    #region helpers
    public static function newCustomProductVariantSuffix(string $family_id): string
    {
        $ret = ProductFamily::find($family_id)->products
            ->pluck("id")
            ->map(fn ($id) => (int) Str::after($id, self::CUSTOM_PRODUCT_VARIANT_SUFFIX_SEPARATOR))
            ->sort()
            ->last();
        $ret = self::CUSTOM_PRODUCT_VARIANT_SUFFIX_SEPARATOR . Str::padLeft($ret + 1, 2, "0");
        return $ret;
    }

    public static function getByFrontId(string $front_id): Product
    {
        $main_part = preg_match("/\d{3}\.\d{3}/", $front_id, $matches) ? $matches[0] : null;
        $products = Product::where("product_family_id", ProductFamily::CUSTOM_PRODUCT_GIVEAWAY . $main_part)->get();

        if ($products->count() == 1) return $products->first();

        $suffix = substr($front_id, -3);
        return $products->firstWhere(fn ($p) => Str::endsWith($p->id, $suffix));
    }
    #endregion
}
