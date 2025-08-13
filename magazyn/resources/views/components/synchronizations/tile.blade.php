@props([
    "moduleName",
    "sync",
])

@php $st = $sync->{$moduleName."_import"}->get("synch_status"); @endphp

<x-magazyn-section :title="$sync->{$moduleName.'_import_enabled'} ? $sync::STATUSES[$st ?? -1][0] : 'Wyłączona'"
    :class="$sync->{$moduleName.'_import_enabled'} ? $sync::STATUSES[$st ?? -1][1] : 'ghost'"
>
    <x-slot:buttons>
        @if ($sync->{$moduleName."_import_enabled"})
        <span class="button" onclick="setSync('enable', '{{ $sync->supplier_name }}', '{{ $moduleName }}', false)" title="Wyłącz">🟥</span>
        @else
        <span class="button" onclick="setSync('enable', '{{ $sync->supplier_name }}', '{{ $moduleName }}', 1)" title="Włącz">🟢</span>
        @endif

        <span class="button" onclick="setSync('reset', '{{ $sync->supplier_name }}', '{{ $moduleName }}')" title="Resetuj">🔃</span>
    </x-slot:buttons>

    <div>
        @if ($sync->{$moduleName."_import_enabled"})
        <div>
            <span class="{{ $sync::STATUSES[$st ?? -1][1] }}"></span>

            {{ $sync->{$moduleName."_import"}->get("current_external_id") }}

            @if (Cache::has(\App\Jobs\SynchronizeJob::getLockName("in_progress", $sync->supplier_name, $moduleName))
                || Cache::has(\App\Jobs\SynchronizeJob::getLockName("finished", $sync->supplier_name, $moduleName))
            )
            <span title="Integracja jest zablokowana przed restartem">🔒</span>
            @endif
        </div>

        <div>
            <progress value="{{ $sync->{$moduleName."_import"}->get("progress") }}" max="100"></progress>
            {{ $sync->{$moduleName."_import"}->get("progress") }}%
        </div>
        @endif

        <span class="grid" style="--col-count: 2; gap: 0;">
            @foreach ($sync->timestampSummary($moduleName) as $label => $summary_item)
            <span>{{ $label }} {{ $summary_item ?: "—" }}</span>
            @endforeach
        </span>
    </div>
</x-magazyn-section>
