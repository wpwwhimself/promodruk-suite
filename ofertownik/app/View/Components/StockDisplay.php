<?php

namespace App\View\Components;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\View\Component;

class StockDisplay extends Component
{
    public $stockData;
    public $productStockData;
    /**
     * Create a new component instance.
     */
    public function __construct(
        public Product $product,
    )
    {
        $this->product = $product;

        try
        {
            $this->stockData = Http::get(env("MAGAZYN_API_URL") . "stock/" . $product->product_family_id)
                ->collect();

            if ($this->stockData == "custom") return;
            $this->productStockData = $this->stockData->firstWhere("id", $product->id);
        }
        catch (\Exception $e)
        {
            $this->productStockData = [
                "current_stock" => "b.d.",
                "future_delivery_amount" => null,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.stock-display');
    }
}
