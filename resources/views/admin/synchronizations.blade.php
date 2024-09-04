@extends("layouts.admin")
@section("title", "Synchronizacje")

@section("content")

<style>
.table {
    --col-count: 8;
    grid-template-columns: repeat(var(--col-count), auto);
}
</style>
<div class="table">
    <span class="head">Dostawca</span>
    <span class="head">Synch. produktów</span>
    <span class="head">Synch. stanów mag.</span>
    <span class="head">Status</span>
    <span class="head">Postęp</span>
    <span class="head">Obecne ID</span>
    <span class="head">Ostatni import</span>
    <span class="head">Akcje</span>
    <hr>

    @foreach ($synchronizations as $sync)
    <span>{{ $sync->supplier_name }}</span>
    <a href="{{ route('synch-enable', ['supplier_name' => $sync->supplier_name, 'mode' => 'product', 'enabled' => intval(!$sync->product_import_enabled)]) }}">
        @if ($sync->product_import_enabled)
        <span class="success">Włączona</span>
        @else
        <span class="danger">Wyłączona</span>
        @endif
    </a>
    <a href="{{ route('synch-enable', ['supplier_name' => $sync->supplier_name, 'mode' => 'stock', 'enabled' => intval(!$sync->stock_import_enabled)]) }}">
        @if ($sync->stock_import_enabled)
        <span class="success">Włączona</span>
        @else
        <span class="danger">Wyłączona</span>
        @endif
    </a>
    <span class="{{ $sync->status[1] }}">{{ $sync->status[0] }}</span>
    <span>{{ $sync->progress }}%</span>
    <span>{{ $sync->current_external_id }}</span>
    <span>{{ $sync->last_sync_started_at }}</span>
    <span>
        <a href="{{ route('synch-reset', ['supplier_name' => $sync->supplier_name]) }}">Resetuj</a>
    </span>
    @endforeach
</div>

<script>
setTimeout(() => {
    window.location.reload()
}, 10e3);
</script>

@endsection
