@extends("layouts.admin")
@section("title", implode(" | ", [$attribute->name ?? "Nowa cecha podstawowa", "Edycja cechy podstawowej"]))

@section("content")

<form action="{{ route('update-main-attributes') }}" method="post">
    @csrf
    <input type="hidden" name="id" value="{{ $attribute?->id }}">

    <x-input-field type="text" label="Nazwa" name="name" :value="$attribute?->name" />
    <x-input-field type="color" label="Kolor" name="color" :value="$attribute?->color" />
    <x-input-field type="TEXT" label="Opis" name="description" :value="$attribute?->description" />

    <div class="flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($attribute)
        <button type="submit" name="mode" value="delete" class="danger">Usuń</button>
        @endif
    </div>
    <div class="flex-right center">
        <a href="{{ route('attributes') }}">Wróć</a>
    </div>
</form>

@endsection
