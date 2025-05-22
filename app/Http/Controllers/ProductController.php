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
            ? ProductFamily::with(array_filter([
                "products",
                in_array("markings", $rq->get("include", [])) ? "products.markings" : null,
                "products.productFamily",
            ]))
            : Product::with(array_filter([
                in_array("markings", $rq->get("include", [])) ? "markings" : null,
                "productFamily",
            ]))
        )
            ->whereIn("id", $rq->get("ids"))
            ->orderByRaw("FIELD(id, " . implode(",", array_map(fn ($id) => "'$id'", $rq->get("ids"))) . ")")
            ->get();
        return response()->json($data);
    }

    public function getProductsForImport(string $source, ?string $category = null, ?string $query = null)
    {
        $data = collect();
        if ($category === "---") $category = null;
        if ($category || $query) {
            // all matching products
            $d = ProductFamily::where("source", "$source")
                ->where(function ($q) use ($category, $query) {
                    if ($category)
                        $q = $q->where("original_category", $category);
                    if ($query)
                        foreach(explode(";", $query) as $qstr) $q = $q->orWhere("id", "like", "%$qstr%");
                    return $q;
                });
        } else {
            // only categories
            $d = ProductFamily::where("source", "$source")
                ->select("original_category")
                ->distinct();
        }
        $data = $data->merge($d->get());

        return response()->json($data);
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
        $data = Product::with(["productFamily"])
            ->whereHas("productFamily", fn ($q) => $q->whereIn("source", request("suppliers")))
            ->where(fn($q) => $q
                ->where("id", "like", "%".request("q", "")."%")
                ->orWhere("name", "like", "%".request("q", "")."%")
                ->orWhere("variant_name", "like", "%".request("q", "")."%")
            )
            ->orderBy("id")
            ->selectRaw("id, CONCAT(name, ' | ', variant_name, ' (', id, ')') as text, product_family_id")
            ->limit(20)
            ->get()
            ->toArray();

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
    public function getAatrTile(int $id, string $variant_name)
    {
        return view("components.variant-tile", ["variant" => AltAttribute::find($id)->forTile($variant_name)]);
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
