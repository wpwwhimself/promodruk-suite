@extends("layouts.admin")
@section("title", "Importuj produkt dostawcy")

@section("content")

<form method="POST" action="{{ route('products-import-fetch') }}">
    @csrf
    <label for="product_code">Podaj kod produktu</label>
    <input type="text" name="product_code" id="product_code">

    <button type="submit" onclick="">Przejdź</button>
</form>

@endsection
