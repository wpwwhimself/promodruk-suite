@extends("layouts.app", compact("title"))

@section("content")

@foreach ($data as $row)
<p>{{ implode(" / ", [
    $row["index"],
    "[nazwa]",
    $row["quantity"] . " szt.",
    "Planowana dostawa: " . "[data]",
]) }}</p>
@endforeach

@endsection
