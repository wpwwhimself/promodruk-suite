@extends("layouts.admin")
@section("title", "Import produktów")

@section("content")

<form action="{{ route('products-import-fetch') }}" method="post" class="flex-down center">
    @csrf
    <p>Podaj kod, aby wyszukać powiązane produkty w Magazynie.</p>

    <x-input-field type="text" name="code" label="SKU" required />

    <div class="flex-right center">
        <x-button action="submit" label="Znajdź" icon="search" />
        <x-button :action="route('products')" label="Wróć" icon="arrow-left" />
    </div>
</form>

@endsection
