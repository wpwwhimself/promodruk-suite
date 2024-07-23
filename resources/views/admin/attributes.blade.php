@extends("layouts.admin")
@section("title", "Cechy")

@section("content")

<ul>
    @forelse ($attributes as $attribute)
    <li>
        <a href="{{ route("attributes-edit", $attribute->id) }}">{{ $attribute->name }}</a>
        ({{ $attribute->variants()->count() }} wariantów)
    </li>
    @empty
    <li class="ghost">Brak zdefiniowanych atrybutów</li>
    @endforelse
</ul>

<a href="{{ route("attributes-edit") }}">Dodaj atrybut</a>

@endsection
