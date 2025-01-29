<span style="grid-column: 1;"></span>
<span class="head" style="grid-column: 2 / span 3; justify-self: center;">Synchronizacja</span>
<span class="head" style="grid-column: 8 / span 2; justify-self: center;">Ostatni import</span>
<span style="grid-column: 10;"></span>

<span class="head">Dostawca</span>
<span class="head button" onclick="setSync('enable', null, 'product', {{ var_export($sync_statuses->product == 0, true) }})">
    Produkty
</span>
<span class="head button" onclick="setSync('enable', null, 'stock', {{ var_export($sync_statuses->stock == 0, true) }})">
    Stany mag.
</span>
<span class="head button" onclick="setSync('enable', null, 'marking', {{ var_export($sync_statuses->marking == 0, true) }})">
    Znakowania
</span>
<span class="head">Status</span>
<span class="head">Postęp</span>
<span class="head">Obecne ID</span>
<span class="head">Podjęto</span>
<span class="head">Szczegóły</span>
<span class="head">Akcje</span>
<hr style="grid-column: 1 / span 10;">

@foreach ($synchronizations as $quickness => $syncs)
<h3 style="grid-column: 1 / span 10;">{{ $quickness_levels[$quickness] }}</h3>

@foreach ($syncs as $sync)
<span><a href="{{ route('synchronizations-edit', ['supplier_name' => $sync->supplier_name]) }}">{{ $sync->supplier_name }}</a></span>
<span class="button"
    onclick="setSync('enable', '{{ $sync->supplier_name }}', 'product', {{ intval(!$sync->product_import_enabled) }})"
>
    @if ($sync->product_import_enabled)
    <span class="success">Włączona</span>
    @else
    <span class="danger">Wyłączona</span>
    @endif
</span>
<span class="button"
    onclick="setSync('enable', '{{ $sync->supplier_name }}', 'stock', {{ intval(!$sync->stock_import_enabled) }})"
>
    @if ($sync->stock_import_enabled)
    <span class="success">Włączona</span>
    @else
    <span class="danger">Wyłączona</span>
    @endif
</span>
<span class="button"
    onclick="setSync('enable', '{{ $sync->supplier_name }}', 'marking', {{ intval(!$sync->marking_import_enabled) }})"
>
    @if ($sync->marking_import_enabled)
    <span class="success">Włączona</span>
    @else
    <span class="danger">Wyłączona</span>
    @endif
</span>
<span class="{{ $sync->status[1] }}">{{ $sync->status[0] }}</span>
<span>{{ $sync->progress }}%</span>
<span>{{ $sync->current_external_id }}</span>
<span>
    {{ $sync->last_sync_started_at->diffForHumans() }}
</span>
<span>
    {!! nl2br($sync->timestamp_summary) !!}
</span>
<span>
    <span class="button" onclick="setSync('reset', '{{ $sync->supplier_name }}')">Resetuj</span>
    <span class="button" onclick="setSync('enable', '{{ $sync->supplier_name }}', null, false)">Wyłącz</span>
</span>
@endforeach

@endforeach
