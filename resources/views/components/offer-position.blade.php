@props([
    "marking",
])

<div class="offer-position flex-right stretch top">
    <div class="data grid" style="--col-count: 1;">
        <h4>
            {{ $marking["technique"] }}
            <small class="ghost">{{ $marking["print_size"] }}</small>
        </h4>

        @foreach ($marking["main_price_modifiers"] ?? ["" => null] as $label => $modifier)
        @if (!empty($modifier)) <span>{{ $label }}</span> @endif
        <ul>
            @foreach ($marking["quantity_prices"] as $requested_quantity => $price_per_unit)
            @php
            $mod_price_per_unit = eval("return $price_per_unit $modifier;");
            @endphp
            <li>
                {{ $requested_quantity }} szt:
                <strong>{{ as_pln($mod_price_per_unit * $requested_quantity) }}</strong>
                <small class="ghost">{{ as_pln($mod_price_per_unit) }}/szt.</small>
            </li>
            @endforeach
        </ul>
        @endforeach
    </div>

    <div class="images flex-right">
        <img class="thumbnail"
            src="{{ $marking["images"][0] }}"
            {{ Popper::pop("<img src='" . $marking["images"][0] . "' />") }}
        />
    </div>
</div>
