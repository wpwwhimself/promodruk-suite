@props([
    "data",
])

<div class="flex down">
    <span class="ghost">Liczba znalezionych rodzin produktów: {{ count($data) }}</span>

    <div class="grid but-mobile down" style="--col-count: 3;">
        @foreach ($data ?? [] as $product)
        <span>
            <img class="inline" src="{{ $product->thumbnails->first(fn($img) => $img !== null) }}" alt="miniatura" />
            <a href="{{ route('products-edit', ['id' => $product->family_prefixed_id]) }}">{{ $product }}</a>
            <small class="ghost">{{ $product->family_prefixed_id }}</small>
            <span @class([
                "accent",
                "success" => $product->visible == 2,
                "danger" => $product->visible == 1,
                "error" => $product->visible == 0,
            ])>
                <x-shipyard::app.icon :name="$product->visible > 0 ? 'eye' : 'eye-off'" />
            </span>
        </span>
        @endforeach
    </div>
</div>
