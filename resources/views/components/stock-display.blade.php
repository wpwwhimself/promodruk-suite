@if ($product->family->count() > 1)

@php
$what_can_be_seen = array_filter([
    "zdjęcia" => !$product->has_no_unique_images,
    "stan magazynowy" => isset($productStockData),
]);
@endphp

<p>
    Dostępne {{ lcfirst($product->color["attribute_name"] ?? "Kolory") }}:
    {{ count($what_can_be_seen) ? "(wybierz, aby zobaczyć ".implode(" i ", array_keys($what_can_be_seen)).")" : "" }}
</p>

<div class="flex-down">
    <div class="flex-right wrap">
        @foreach ($product->family as $alt)
        <x-variant-tile :variant="collect($alt->color)"
            :active="$alt->id == $product->id && !$product->has_no_unique_images"
            :link="$product->has_no_unique_images ? null : route('product', ['id' => $alt->front_id])"
            pop="<span>{{ $alt->color['name'] }}{{ (isset($productStockData) && !$alt->sizes) ? ' / ' .($stockData?->firstWhere('id', $alt->id)['current_stock'] ?? '-'). ' szt.' : '' }}</span>"
        />
        @endforeach
    </div>

    @unless ($product->has_no_unique_images)
    <span>
        Wybrany kolor: <u style="font-weight: bold;">{{ $product->color["name"] }}</u>
    </span>
    @endunless

    @if ($product->sizes)
    <div class="grid size-stock-table">
        <div role="labels" class="flex-down no-gap">
            <span>Rozmiar:</span>
            <span>Stan mag.:</span>
        </div>
        <div role="amounts" class="flex-right wrap">
            @foreach ($product->sizes as $size)
            <div class="flex-down no-gap">
                <span>
                    <x-size-tag :size="$size"
                        pop="<span>Rozmiar {{ $size['size_name'] }}</span>"
                    />
                </span>
                <span>
                    {{ $stockData?->firstWhere("id", $size['full_sku'])["current_stock"] ?? 0 }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

@unless (empty($product->color["color"]))
<div class="stock-display flex-right">
    @if (isset($productStockData) && !$product->sizes)
    <b>{{ $productStockData["current_stock"] }} szt.</b>

    <span>
    Przewid. dost.: {{ $productStockData["future_delivery_amount"] ? "$productStockData[future_delivery_amount] szt., $productStockData[future_delivery_date]" : "brak" }}
    </span>
    @endif
</div>
@endunless
