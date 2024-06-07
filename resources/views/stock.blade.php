@extends("layouts.app", compact("title"))

@section("content")

<div class="top-bar">
    <h1>Stan magazynowy</h1>
    <div>
        <span>na dzień {{ $now->format("d.m.Y ") }}</span>
        <span>godz. {{ $now->format("H:i:s") }}</span>
    </div>
</div>

<style>
.row {
    grid-template-columns: 1fr 4fr 2fr 2fr 3fr;
}
</style>
<div class="table">
    <div class="row head">
        <span>Kod</span>
        <span>Nazwa</span>
        <span>Kolor</span>
        <span>Akt. stan mag.</span>
        <span>Planowana dostawa</span>
    </div>
    <hr>
    @foreach ($data as $row)
    <div class="row">
        <span>{{ $row["index"] }}</span>
        <span>{{ collect($row["names"])->first(fn ($el) => $el["language"] == "pl")["title"] }}</span>
        <span></span>
        <b>{{ $row["quantity"] }} szt.</b>
        <span>{{ processFutureDelivery($row["future_delivery"]) }}</span>
    </div>
    @endforeach
</div>

@endsection
