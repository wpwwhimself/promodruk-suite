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

        $this->stockData = Http::get(env("MAGAZYN_API_URL") . "stock/" . $product->product_family_id)
            ->collect();
        $this->productStockData = $this->stockData->firstWhere("id", $product->id);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.stock-display');
    }
}
