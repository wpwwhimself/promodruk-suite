@extends("layouts.app", compact("title"))

@section("content")

<div class="top-bar">
    <h1>Stan magazynowy</h1>
    <div class="flex-down">
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
    @forelse ($data as $row)
    <div class="row">
        <span>{{ $row["index"] }}</span>
        <span>
            <img
                src="{{ collect($row["image"])
                    ->sortBy("url")
                    ->first()["url"]
                }}"
                alt="{{ collect($row["names"])->first(fn ($el) => $el["language"] == "pl")["title"] }}"
                class="inline"
            >
            {{ collect($row["names"])->first(fn ($el) => $el["language"] == "pl")["title"] }}
        </span>
        <span>{{ collect($row["additional"])->first(fn ($el) => $el["item"] == "color_product")["value"] }}</span>
        <b>{{ $row["quantity"] }} szt.</b>
        <span>{{ processFutureDelivery($row["future_delivery"]) }}</span>
    </div>
    @empty
    <div class="row">
        <span class="ghost" style="grid-column: 1 / span 5">
            Nie udało się znaleźć produktu o kodzie {{ $product_code }}
        </span>
    </div>
    @endforelse
</div>

<a href="{{ route('main') }}">Wróć do wyszukiwania</a>

@endsection
