<?php

namespace App\Http\Controllers;

use App\Models\AltAttribute;
use App\Models\Attribute;
use App\Models\CustomSupplier;
use App\Models\MainAttribute;
use App\Models\PrimaryColor;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductMarking;
use App\Models\ProductSynchronization;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function getProducts(?string $id = null, bool $soft = false)
    {
        $data = ($id)
            ? ($soft
                ? Product::with("stock")->where("id", "like", "%$id%")->get()
                : Product::with("stock")->findOrFail($id)
            )
            : Product::all();
        return response()->json($data);
    }
    public function getProductsByIds(Request $rq)
    {
        if ($rq->missing("ids")) abort(400, "No product IDs supplied");
        $data = ($rq->has("families")
            ? ProductFamily::with(array_merge(
                ["products", "products.productFamily"],
                array_map(fn ($incl) => "products.$incl", $rq->get("include", [])),
            ))
            : Product::with(array_merge(
                ["productFamily"],
                array_map(fn ($incl) => "$incl", $rq->get("include", [])),
            ))
        )
            ->whereIn("id", $rq->get("ids"))
            ->orderByRaw("FIELD(id, " . implode(",", array_map(fn ($id) => "'$id'", $rq->get("ids"))) . ")")
            ->get();
        return response()->json($data);
    }

    public function getProductsForImport(Request $rq)
    {
        [$source, $category, $query] = [$rq->source, $rq->category, $rq->get("query")];

        if ($category === "---") $category = null;
        if ($category || $query) {
            // all matching products
            $d = ProductFamily::with("products");
            if ($source) $d = $d->where("source", "$source");
            $d = $d->where(function ($q) use ($category, $query) {
                if ($category)
                    $q = $q->orWhereIn("original_category", $category);
                if ($query)
                    $q = $q->orWhereIn("id", explode(";", $query));
                if (array_search("%new%", $category) !== false)
                    $q = $q->orWhere("marked_as_new", true);
                return $q;
            });

            return response()->json($d->get());
        } else {
            // only categories
            $d = ProductFamily::where("source", "$source")
                ->select("original_category")
                ->distinct()
                ->get()
                ->map(fn ($cat) => $cat["original_category"])
                ->toArray();

            if (ProductFamily::where("source", $source)->where("marked_as_new", true)->exists()) {
                $d = Arr::prepend($d, "✨ Nowości ✨");
            }

            return response()->json($d);
        }
    }
    public function getProductsForRefresh(Request $rq)
    {
        if (empty($rq->get("ids"))) return response("No product IDs supplied", 400);

        $products = ($rq->has("families")
            ? ProductFamily::with(["products", "products.productFamily"])
            : Product::with(["productFamily"])
        )
            ->whereIn("id", $rq->get("ids"))
            ->get();
        $missing = collect($rq->get("ids"))->diff($products->pluck("id"));

        return response()->json(compact(
            "products",
            "missing",
        ));
    }

    public function getProductsForMarkings()
    {
        $data = Product::with(["productFamily", "stock"])
            ->whereHas("productFamily", fn ($q) => $q->whereIn("source", request("suppliers")))
            ->where(fn($q) => $q
                ->where("id", "like", "%".request("q", "")."%")
                ->orWhere("name", "like", "%".request("q", "")."%")
                ->orWhere("variant_name", "like", "%".request("q", "")."%")
            )
            ->orderBy("id")
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                "id" => $p->id,
                "text" => $p->name . " | " . $p->variant_name . " (" . $p->id . ")" . " / " . ($p->stock?->current_stock ?? 0) . " szt.",
                "product_family_id" => $p->productFamily->id,
            ]);

        return response()->json([
            "results" => $data,
        ]);
    }

    public function getProductsForCustomDiscounts()
    {
        $data = ProductFamily::whereIn("source", request("suppliers"))
            ->where(fn($q) => $q
                ->where("id", "like", "%".request("q", "")."%")
                ->orWhere("name", "like", "%".request("q", "")."%")
            )
            ->orderBy("id")
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                "id" => $p->id,
                "text" => $p->name . " (" . $p->id . ")",
            ]);

        return response()->json([
            "results" => $data,
        ]);
    }

    public function getMainAttributes(?int $id = null)
    {
        $data = ($id)
            ? MainAttribute::findOrFail($id)
            : MainAttribute::get();
        return response()->json($data);
    }

    public function getPrimaryColors(?int $id = null)
    {
        $data = ($id)
            ? PrimaryColor::findOrFail($id)
            : PrimaryColor::get();
        return response()->json($data);
    }

    public function getAatrs(?int $id = null)
    {
        $data = ($id)
            ? AltAttribute::findOrFail($id)
            : AltAttribute::get();
        return response()->json($data);
    }

    public function getSuppliers()
    {
        $data = ProductSynchronization::select("supplier_name")->get()
            ->map(function ($s) {
                $handlerName = "App\DataIntegrators\\" . $s["supplier_name"] . "Handler";
                $handler = new $handlerName($s);
                return [
                    "name" => $s["supplier_name"],
                    "source" => $s["supplier_name"],
                    "prefix" => $handler->getPrefix(),
                ];
            })
            ->merge(CustomSupplier::all()
                ->map(fn ($s) => [
                    "name" => $s["name"],
                    "source" => ProductFamily::CUSTOM_PRODUCT_GIVEAWAY . $s["id"],
                    "prefix" => $s["prefix"],
                ])
            );
        return response()->json($data);
    }

    public function getColorTile(string $color_name) // deprecated
    {
        return view("components.variant-tile", ["color" => MainAttribute::where("name", $color_name)->firstOrFail()]);
    }
    public function getPrimaryColorTile(string $color_name)
    {
        return view("components.variant-tile", ["color" => PrimaryColor::where("name", $color_name)->firstOrFail()]);
    }
    public function getAatrTile(string $product_family_id, string $variant_name)
    {
        $productFamily = ProductFamily::findOrFail($product_family_id);
        return view("components.variant-tile", ["variant" => $productFamily->attributeForTile($variant_name)]);
    }

    public function getProductColors(Request $rq)
    {
        $colors = ($rq->has("families"))
            ? ProductFamily::whereIn("id", $rq->get("families"))
                ->get()
                ->mapWithKeys(fn ($f) => [$f->id => $f->products->map(fn ($p) => $p->color)])
            : Product::whereIn("id", $rq->get("products"))
                ->get()
                ->mapWithKeys(fn ($p) => [$p->id => $p->color]);
        return response()->json(compact(
            "colors",
        ));
    }
}
