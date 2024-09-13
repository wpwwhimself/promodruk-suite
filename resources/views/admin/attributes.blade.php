@extends("layouts.admin")
@section("title", "Cechy")

@section("content")

<h2>Cechy podstawowe</h2>

@if (userIs("Administrator"))
<a href="{{ route("main-attributes-prune") }}">Usuń nieużywane cechy podstawowe</a>
@endif

<div class="flex-right">
    @if (!empty(request("show")))
        <a href="{{ route("attributes") }}">Pokaż wszystkie</a>
    @endif
    @if (request("show") != "missing")
        <a href="{{ route("attributes", ["show" => "missing"]) }}">Pokaż nieopisane</a>
    @endif
    @if (request("show") != "filled")
        <a href="{{ route("attributes", ["show" => "filled"]) }}">Pokaż opisane</a>
    @endif
</div>

<ul>
    @php
        $data = (request("show") == "missing")
            ? $mainAttributes->where("color", "")
            : (request("show") == "filled"
                ? $mainAttributes->where("color", "!=", "")
                : $mainAttributes
            )
    @endphp
    @forelse ($data as $attribute)
    <li>
        <a href="{{ route("main-attributes-edit", $attribute->id) }}">
            <x-color-tag :color="$attribute" />
            {{ $attribute->name }}
        </a>

        @if (isset($productExamples[$attribute->name]) && $attribute->color == "")
        <small class="ghost">(w produktach: {{ $productExamples[$attribute->name]->pluck("id")->join("; ") }})</small>
        @endif
    </li>
    @empty
    <li class="ghost">Brak {{ empty(request("show")) ? "zdefiniowanych" : "" }} cech podstawowych</li>
    @endforelse
</ul>

<a href="{{ route("main-attributes-edit") }}">Dodaj cechę główną</a>

<h2>Cechy dodatkowe</h2>

<ul>
    @forelse ($attributes as $attribute)
    <li>
        <a href="{{ route("attributes-edit", $attribute->id) }}">{{ $attribute->name }}</a>
        ({{ $attribute->variants()->count() }} wariantów)
    </li>
    @empty
    <li class="ghost">Brak zdefiniowanych cech</li>
    @endforelse
</ul>

<a href="{{ route("attributes-edit") }}">Dodaj cechę</a>

@endsection
