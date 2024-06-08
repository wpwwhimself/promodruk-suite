@extends("layouts.app", compact("title"))

@section("content")

<div class="top-bar flex-right">
    <h1>Stan magazynowy</h1>
    <div class="flex-down">
        <span>na dzień {{ $now->format("d.m.Y ") }}</span>
        <span>godz. {{ $now->format("H:i:s") }}</span>
    </div>
</div>

<style>
.table {
    --col-count: 5;
    grid-template-columns: repeat(var(--col-count), auto);
}
</style>
<div class="table">
    <span class="head">Kod</span>
    <span class="head">Nazwa</span>
    <span class="head">Kolor</span>
    <span class="head">Akt. stan mag.</span>
    <span class="head">Planowana dostawa</span>
    <hr>
    @forelse ($data as $row)
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
    @empty
    <span class="ghost" style="grid-column: 1 / span 5">
        Nie udało się znaleźć produktu o kodzie {{ $product_code }}
    </span>
    @endforelse
</div>

{{-- <a href="{{ route('main') }}">Wróć do wyszukiwania</a> --}}

@endsection
