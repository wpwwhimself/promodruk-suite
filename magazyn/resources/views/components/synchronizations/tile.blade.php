@props([
    "moduleName",
    "sync",
])

@php $st = $sync->{$moduleName."_import"}->get("synch_status"); @endphp

<x-shipyard.app.card
    :title="$sync->{$moduleName.'_import_enabled'} ? $sync::STATUSES[$st ?? -1][0] : 'Wyłączona'"
    class="{{ $sync->{$moduleName.'_import_enabled'} ? '' : 'ghost' }}"
>
    <x-slot:actions>
        <div class="flex right" style="display: inline-flex;">
            @if ($sync->{$moduleName."_import_enabled"})
            <span class="button small" onclick="setSync('enable', '{{ $sync->supplier_name }}', '{{ $moduleName }}', false)" @popper(Wyłącz)>🟥</span>
            @else
            <span class="button small" onclick="setSync('enable', '{{ $sync->supplier_name }}', '{{ $moduleName }}', 1)" @popper(Włącz)>🟢</span>
            @endif

            <span class="button small" onclick="setSync('reset', '{{ $sync->supplier_name }}', '{{ $moduleName }}')" @popper(Resetuj)>🔃</span>
        </div>
    </x-slot:actions>

    @if ($sync->{$moduleName."_import_enabled"})
    <div>
        <span class="{{ $sync::STATUSES[$st ?? -1][1] }}"></span>

        {{ $sync->{$moduleName."_import"}->get("current_external_id") }}
    </div>

    <div>
        <progress value="{{ $sync->{$moduleName."_import"}->get("progress") }}" max="100"></progress>
        {{ $sync->{$moduleName."_import"}->get("progress") }}%

        @if (Cache::has(\App\Jobs\SynchronizeJob::getLockName("in_progress", $sync->supplier_name, $moduleName)))
        <span>🔒 do {{ \Carbon\Carbon::parse(Cache::get(\App\Jobs\SynchronizeJob::getLockName("in_progress", $sync->supplier_name, $moduleName)))->diffForHumans() }}</span>
        @elseif (Cache::has(\App\Jobs\SynchronizeJob::getLockName("finished", $sync->supplier_name, $moduleName)))
        <span>🔒 do {{ \Carbon\Carbon::parse(Cache::get(\App\Jobs\SynchronizeJob::getLockName("finished", $sync->supplier_name, $moduleName)))->diffForHumans() }}</span>
        @endif
    </div>
    @endif

    <span class="grid" style="--col-count: 2; gap: 0;">
        @foreach ($sync->timestampSummary($moduleName) as $summary_item)
        <span
            @isset($summary_item["class"]) class="{{ $summary_item["class"] }}" @endisset
        >
            {{ $summary_item["icon"] }} {{ $summary_item["value"] ?: "—" }}
        </span>
        @endforeach
    </span>
</x-shipyard.app.card>
