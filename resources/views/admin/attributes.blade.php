@extends("layouts.admin")
@section("title", "Cechy")

@section("content")

<h2>Cechy podstawowe</h2>

<ul>
    @forelse ($mainAttributes as $attribute)
    <li>
        <a href="{{ route("main-attributes-edit", $attribute->id) }}">
            <x-color-tag :color="$attribute" />
            {{ $attribute->name }}
        </a>
    </li>
    @empty
    <li class="ghost">Brak zdefiniowanych cech podstawowych</li>
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
