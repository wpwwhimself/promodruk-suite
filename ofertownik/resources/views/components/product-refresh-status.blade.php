@props([
    "refreshData" => [],
    "unsynced" => null,
])

@php
$frontData = ($refreshData) ? [
    "status" => $refreshData["status"] ?? "–",
    "ID" => $refreshData["current_id"] ?? "–",
    "%" => ($refreshData["progress"] ?? 0) . "%",
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
    </div>
    @else
    <x-shipyard.app.loader horizontal />
    @endif

    <div class="flex right center middle">
        <x-shipyard.ui.button
            :action="route('products-import-refresh')"
            label="Wymuś teraz"
            icon="refresh"
            class="primary"
        />
        <x-shipyard.ui.button
            :action="route('products-import-refresh', ['anew' => true])"
            label="Wymuś teraz od nowa"
            icon="refresh"
            class="danger"
        />
    </div>

    <div class="flex right center middle">
        <strong>Produkty w katalogu bez odpowiedników w Magazynie:</strong>
        <span>
            {{ $unsynced ?? "—" }}
            @if ($unsynced > 0)
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

function getData() {
    fetch(`{{ route("products-import-refresh-status") }}`)
        .then(res => res.json())
        .then(({data, table}) => {
            document.querySelector("#product-refresh-status").innerHTML = table;
            document.querySelector(`#product-refresh-status .loader`)?.classList.add("hidden");
        })
        .catch(err => console.error(err))
        .finally(() => {
            setTimeout(getData, 3e3);
        });
}
getData();
</script>
