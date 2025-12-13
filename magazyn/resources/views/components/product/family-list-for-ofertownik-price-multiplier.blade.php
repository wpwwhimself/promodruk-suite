@props([
    "families",
    "tally" => null,
])

<div class="grid" style="--col-count: 3" role="list">
    @forelse ($families as $family)
    @php
    $multipliers = $family->products->pluck("ofertownik_price_multiplier")->countBy(fn ($m) => "$m")->sortDesc();
    $main_multiplier = $multipliers->keys()->first();
    @endphp

    <div>
        <x-product.family :family="$family" />
        <span class="accent tertiary"
            {{ Popper::pop($multipliers->map(fn ($count, $val) => "$count prod. z mnożnikiem ×$val")->join(", ")) }}
        >
            ×{{ $main_multiplier }}
        </span>
    </div>
    @empty
    <span class="ghost">Brak produktów do wyświetlenia</span>
    @endforelse

    @if ($tally)
    <div class="ghost flex right center" style="grid-column: span 3">
        <span>{{ $tally }}</span>
    </div>
    @endif
</div>
