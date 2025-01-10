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
        @foreach ($product->family->where("size_name", $product->size_name) as $alt)
        <x-color-tag :color="collect($alt->color)"
            :active="$alt->id == $product->id"
            :link="route('product', ['id' => $alt->id])"
            pop="<span>{{ $alt->color['name'] }}{{ isset($productStockData) ? ' / ' .($stockData?->firstWhere('id', $alt->id)['current_stock'] ?? '-'). ' szt.' : '' }}</span>"
        />
        @endforeach
    </div>

    @if ($product->size_name)
    <div class="flex-right wrap">
        @foreach ($product->family->where("color", $product->color) as $alt)
        <x-size-tag :size="$alt->size_name"
            :active="$alt->id == $product->id"
            :link="route('product', ['id' => $alt->id])"
            pop="<span>Rozmiar {{ $alt->size_name }}{{ isset($productStockData) ? ' / ' .($stockData?->firstWhere('id', $alt->id)['current_stock'] ?? '-'). ' szt.' : '' }}</span>"
        />
        @endforeach
    </div>
    @endif
</div>
@endif

<div class="stock-display flex-right">
    <span>
        Kolor <a href="{{ route('product', ['id' => $product->id]) }}">{{ Str::lcfirst($product->color["name"]) }}</a>
    </span>

    @if ($product->size_name)
    <span>
        Rozmiar <a href="{{ route('product', ['id' => $product->id]) }}">{{ $product->size_name }}</a>
    </span>
    @endif

    @isset ($productStockData)
    <b>{{ $productStockData["current_stock"] }} szt.</b>
    @endisset

    @isset ($productStockData)
    <span>
    Przewid. dost.: {{ $productStockData["future_delivery_amount"] ? "$productStockData[future_delivery_amount] szt., $productStockData[future_delivery_date]" : "brak" }}
    </span>
    @endisset
</div>
