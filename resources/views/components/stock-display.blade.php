@if ($product->family->count() > 1)
<p>
    @isset ($productStockData)
    Wybierz kolor, aby zobaczyć zdjęcia i stan magazynowy
    @else
    Wybierz kolor, aby zobaczyć zdjęcia
    @endisset
</p>

<div class="flex-down">
    <div class="flex-right wrap">
        @foreach ($product->family as $alt)
        <x-color-tag :color="collect($alt->color)"
            :active="$alt->id == $product->id"
            :link="route('product', ['id' => $alt->id])"
            pop="<span>{{ $alt->color['name'] }}{{ (isset($productStockData) && !$alt->sizes) ? ' / ' .($stockData?->firstWhere('id', $alt->id)['current_stock'] ?? '-'). ' szt.' : '' }}</span>"
        />
        @endforeach
    </div>

    @if ($product->sizes)
    <div class="grid size-stock-table">
        <span>Stan mag.:</span>
        @foreach ($product->sizes as $size)
        <span>
            <x-size-tag :size="$size"
                pop="<span>Rozmiar {{ $size['size_name'] }}</span>"
            />
        </span>
        <span>
            {{ $stockData?->firstWhere("id", $size['full_sku'])["current_stock"] ?? 0 }} szt.
        </span>
        @endforeach
    </div>
    @endif
</div>
@endif

<div class="stock-display flex-right">
    <span>
        Kolor <a href="{{ route('product', ['id' => $product->id]) }}">{{ Str::lcfirst($product->color["name"]) }}</a>
    </span>

    @if (isset($productStockData) && !$product->sizes)
    <b>{{ $productStockData["current_stock"] }} szt.</b>

    <span>
    Przewid. dost.: {{ $productStockData["future_delivery_amount"] ? "$productStockData[future_delivery_amount] szt., $productStockData[future_delivery_date]" : "brak" }}
    </span>
    @endif
</div>
