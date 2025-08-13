@extends("layouts.admin")
@section("title", $supervisor?->name ?? "Nowy opiekun")

@section("content")

<form action="{{ route('supervisor-submit') }}" method="post">
    @csrf
    <input type="hidden" name="id" value="{{ $supervisor?->id }}">

    <x-input-field
        name="name"
        label="Imię i nazwisko"
        :value="$supervisor?->name"
    />
    <x-input-field type="email"
        name="email"
        label="Adres email"
        :value="$supervisor?->email"
    />
    <x-input-field type="checkbox"
        name="visible"
        label="Widoczny"
        :value="$supervisor?->visible ?? true"
    />

    <div class="flex-right center">
        <x-button action="submit" label="Zapisz" icon="save" />
        <x-button action="submit" name="method" value="DELETE" label="Usuń" icon="delete" class="danger" />
    </div>
</form>

@endsection
