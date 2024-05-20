@extends("layouts.app", compact("title"))

@section("content")

<h1>Stan magazynowy</h1>
<p>na dzień {{ $now->format("d.m.Y ") }} / godz. {{ $now->format("H:i:s") }}</p>

@foreach ($data as $row)
<p>{{ implode(" / ", [
    $row["index"],
    collect($row["names"])->first(fn ($el) => $el["language"] == "pl")["title"],
    $row["quantity"] . " szt.",
    "Planowana dostawa: " . processFutureDelivery($row["future_delivery"]),
]) }}</p>
@endforeach

@endsection
