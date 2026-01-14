@props([
    "refreshData" => [],
    "unsynced" => collect(),
])

@php
$frontData = ($refreshData) ? [
    "wł." => ($refreshData["enabled"] ?? false) ? "🟢" : "🔴",
    "status" => $refreshData["status"] ?? "–",
    "ID" => $refreshData["current_id"] ?? "–",
    "%" => $refreshData["progress"] . "%",
    "🟢" => ($refreshData["last_sync_started_at"] ?? null) ? Carbon\Carbon::parse($refreshData["last_sync_started_at"])->diffForHumans() : "–",
    "🛫" => ($refreshData["last_sync_zero_at"] ?? null) ? Carbon\Carbon::parse($refreshData["last_sync_zero_at"])->diffForHumans() : "–",
    "🛬" => ($refreshData["last_sync_completed_at"] ?? null) ? Carbon\Carbon::parse($refreshData["last_sync_completed_at"])->diffForHumans() : "–",
    "⏱️" => ($refreshData["last_sync_zero_to_full"] ?? null) ? Carbon\CarbonInterval::seconds($refreshData["last_sync_zero_to_full"])->cascade()->forHumans() : "–",
] : [];
@endphp

<div id="product-refresh-status" class="flex-down center middle">
    @if ($frontData)
    <div class="flex right center middle">
        @foreach ($frontData as $label => $value)
        <div class="flex-down center">
            <strong>{{ $label }}</strong>
            <span>{{ $value }}</span>
        </div>
        @endforeach

        <x-shipyard.ui.button
            :action="route('products-import-refresh')"
            label="Wymuś teraz"
            icon="refresh"
            class="primary"
        />
    </div>
    @else
    <x-shipyard.app.loader horizontal />
    @endif

    <div class="flex right center middle">
        <strong>Produkty w katalogu bez odpowiedników w Magazynie:</strong>
        <span>
            {{ $unsynced->count() }}
            @if ($unsynced->count() > 0)
            🟡
            @else
            🟢
            @endif
        </span>

        <x-shipyard.ui.button
            :action="route('products-unsynced-list')"
            label="Zarządzaj"
            icon="eye"
        />
    </div>
</div>

<script defer>
document.querySelector(`#product-refresh-status .loader`).classList.remove("hidden");
setInterval(() => {
    fetch(`{{ route("products-import-refresh-status") }}`)
        .then(res => res.json())
        .then(({data, table}) => {
            document.querySelector("#product-refresh-status").innerHTML = table;
            document.querySelector(`#product-refresh-status .loader`).classList.add("hidden");
        })
        .catch(err => console.error(err));
}, 2e3);
</script>
