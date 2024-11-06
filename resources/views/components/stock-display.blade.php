@if ($product->family->count() > 1)
<p>
    @isset ($productStockData)
    Wybierz kolor, aby zobaczyć zdjęcia i stan magazynowy
    @else
    Wybierz kolor
    @endisset
</p>

<div class="flex-right wrap">
    @foreach ($product->family as $alt)
    <x-color-tag :color="collect($alt->color)"
        :active="$alt->id == $product->id"
        :link="route('product', ['id' => $alt->id])"
        pop="<span>{{ $alt->color['name'] }}{{ isset($productStockData) ? '/ ' .($stockData->firstWhere('id', $alt->id)['current_stock'] ?? '-'). ' szt.' : '' }}</span>"
    />
    @endforeach
</div>
@endif

<div class="stock-display">
    <span>
        <span style="margin-right: 2em">
            @isset ($productStockData)
            Kolor <a href="{{ route('product', ['id' => $product->id]) }}">{{ Str::lcfirst($product->color["name"]) }}</a>: <b>{{ $productStockData["current_stock"] }} szt.</b>
            @else
            Kolor <a href="{{ route('product', ['id' => $product->id]) }}">{{ Str::lcfirst($product->color["name"]) }}</a>
            @endisset
        </span>
        @isset ($productStockData)
        <span>
        Przewid. dost.: {{ $productStockData["future_delivery_amount"] ? "$productStockData[future_delivery_amount] szt., $productStockData[future_delivery_date]" : "brak" }}
        </span>
        @endisset
    </span>
</div>
