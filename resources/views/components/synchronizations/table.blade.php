<span class="head">Dostawca</span>
<span class="head button" onclick="setSync('enable', null, 'product', {{ var_export($sync_statuses->product == 0, true) }})">
    Synch. produktów
</span>
<span class="head button" onclick="setSync('enable', null, 'stock', {{ var_export($sync_statuses->stock == 0, true) }})">
    Synch. stanów mag.
</span>
<span class="head button" onclick="setSync('enable', null, 'marking', {{ var_export($sync_statuses->marking == 0, true) }})">
    Synch. znakowań
</span>
<span class="head">Status</span>
<span class="head">Postęp</span>
<span class="head">Obecne ID</span>
<span class="head">Ostatni import</span>
<span class="head">Akcje</span>
<hr>

@foreach ($synchronizations as $quickness => $syncs)
<h3 style="grid-column: 1 / span 9;">{{ $quickness_levels[$quickness] }}</h3>

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
    {{ $sync->last_sync_started_at }}
    @if ($sync->last_sync_completed_at)
    <br>
    {{ Carbon\CarbonInterval::seconds($sync->last_sync_elapsed_time)->cascade()->forHumans() }}
    @endif
</span>
<span>
    <span class="button" onclick="setSync('reset', '{{ $sync->supplier_name }}')">Resetuj</span>
    <span class="button" onclick="setSync('enable', '{{ $sync->supplier_name }}', null, false)">Wyłącz</span>
</span>
@endforeach

@endforeach
