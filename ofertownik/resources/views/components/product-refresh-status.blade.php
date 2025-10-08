@props([
    "refreshData" => [],
    "unsynced" => collect(),
])

@php
$frontData = ($refreshData) ? [
    "wł." => $refreshData["enabled"] ? "🟢" : "🔴",
    "status" => $refreshData["status"] ?? "–",
    "ID" => $refreshData["current_id"] ?? "–",
    "%" => $refreshData["progress"] . "%",
    "🟢" => $refreshData["last_sync_started_at"] ? Carbon\Carbon::parse($refreshData["last_sync_started_at"])->diffForHumans() : "–",
    "🛫" => $refreshData["last_sync_zero_at"] ? Carbon\Carbon::parse($refreshData["last_sync_zero_at"])->diffForHumans() : "–",
    "🛬" => $refreshData["last_sync_completed_at"] ? Carbon\Carbon::parse($refreshData["last_sync_completed_at"])->diffForHumans() : "–",
    "⏱️" => $refreshData["last_sync_zero_to_full"] ? Carbon\CarbonInterval::seconds($refreshData["last_sync_zero_to_full"])->cascade()->forHumans() : "–",
] : [];
@endphp

<div id="product-refresh-status" class="flex-down center middle">
    <h3 style="margin: 0;">Odświeżanie z Magazynu</h3>

    <div class="flex-right center middle">
        @forelse ($frontData as $label => $value)
        <div class="flex-down center">
            <strong>{{ $label }}</strong>
            <span>{{ $value }}</span>
        </div>
        @empty
        <span class="ghost">Ładuję...</span>
        @endforelse

        <x-button :action="route('products-import-refresh')" label="Wymuś teraz" icon="refresh" />
    </div>

    <div class="flex-right center middle">
        <strong>Produkty w katalogu bez odpowiedników w Magazynie:</strong>
        <span>
            {{ $unsynced->count() }}
            @if ($unsynced->count() > 0)
            🟡
            @else
            🟢
            @endif
        </span>

        <x-button :action="route('products-unsynced-list')" label="Zarządzaj" icon="eye" />
    </div>
</div>

<script defer>
setInterval(() => {
    fetch(`{{ route("products-import-refresh-status") }}`)
        .then(res => res.text())
        .then(data => document.querySelector("#product-refresh-status").innerHTML = data)
}, 2e3);
</script>
