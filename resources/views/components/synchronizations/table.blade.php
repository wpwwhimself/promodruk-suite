<span class="head">Dostawca</span>
<span class="head">🛒 Produkty</span>
<span class="head">📦 Stany magazynowe</span>
<span class="head">🎨 Znakowania</span>
<span class="head">Akcje</span>
<hr style="grid-column: 1 / span 5;">

@foreach ($synchronizations as $quickness_priority => $synchronizations)

<span style="grid-column: 1 / span 5;">
    <h3>{{ \App\Models\ProductSynchronization::QUICKNESS_LEVELS[$quickness_priority] }}</h3>
</span>

@foreach ($synchronizations as $sync)
<span><a href="{{ route('synchronizations-edit', ['supplier_name' => $sync->supplier_name]) }}">{{ $sync->supplier_name }}</a></span>

@foreach (["product", "stock", "marking"] as $module_name)
<span>
    @if ($sync->{$module_name."_import_enabled"})
    <div>
        @php $st = $sync->{$module_name."_import"}->get("synch_status"); @endphp
        <span class="{{ $sync::STATUSES[$st ?? -1][1] }}">{{ $sync::STATUSES[$st ?? -1][0] }}</span>

        {{ $sync->{$module_name."_import"}->get("current_external_id") }}

        @if (Cache::has(\App\Jobs\SynchronizeJob::getLockName("in_progress", $sync->supplier_name, $module_name))
            || Cache::has(\App\Jobs\SynchronizeJob::getLockName("finished", $sync->supplier_name, $module_name))
        )
        <span title="Integracja jest zablokowana przed restartem">🔒</span>
        @endif
    </div>

    <div>
        <progress value="{{ $sync->{$module_name."_import"}->get("progress") }}" max="100"></progress>
        {{ $sync->{$module_name."_import"}->get("progress") }}%
    </div>

    @else
    <span class="ghost error">wyłączona</span>

    @endif

    <span class="grid" style="--col-count: 2; gap: 0;">
        @foreach ($sync->timestampSummary($module_name) as $label => $summary_item)
        <span>{{ $label }} {{ $summary_item ?: "—" }}</span>
        @endforeach
    </span>
</span>
@endforeach

{{-- <span class="grid", style="--col-count: 3; gap: 0;">
    @foreach (App\Models\ProductSynchronization::MODULES as $name => [$icon, $label])
    @continue ($sync->{$name."_import_enabled"} == 0)
    <span>
        <span title="{{ $label }}">{{ $icon }}</span>
        {{ App\Models\ProductSynchronization::ENABLED_LEVELS[$sync->{$name."_import_enabled"}] }}
    </span>
    @endforeach
</span>
<span class="{{ $sync->status[1] }}">{{ $sync->status[0] }}</span>
<span class="grid" style="grid-template-columns: 4em auto;">
    <progress value="{{ $sync->progress }}" max="100"></progress>
    <span>
        @if ($sync->module_in_progress)
        <span title="{{ App\Models\ProductSynchronization::MODULES[$sync->module_in_progress][1] }}">
            {{ App\Models\ProductSynchronization::MODULES[$sync->module_in_progress][0] }}
        </span>
        @endif
        {{ $sync->progress }}%
    </span>
</span>
<span>{{ $sync->current_external_id }}</span>
<span class="grid" style="--col-count: 2; gap: 0;">
    @foreach ($sync->timestamp_summary as $label => $summary_item)
    <span>{{ $label }} {{ $summary_item ?? "—" }}</span>
    @endforeach
</span> --}}

<span>
    <span class="button" onclick="setSync('reset', '{{ $sync->supplier_name }}')">Resetuj</span>
    @if ($sync->anything_enabled)
    <span class="button" onclick="setSync('enable', '{{ $sync->supplier_name }}', null, false)">Wyłącz</span>
    @endif
</span>
@endforeach

@endforeach
