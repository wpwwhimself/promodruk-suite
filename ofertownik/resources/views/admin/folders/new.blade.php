@extends("layouts.admin")
@section("title", "Nowy folder")

@section("content")

<form action="{{ route('folder-create') }}" method="POST">
    @csrf
    <input type="hidden" name="path" value="{{ request("path", "public") }}">

    <p>Utworzony zostanie nowy folder w katalogu <strong>{{ request("path", "public") }}</strong></p>
    <x-input-field name="name" label="Nazwa" />

    <div class="flex-right center">
        <x-button action="submit" label="Utwórz" icon="folder-add" />
    </div>
</form>

@endsection
