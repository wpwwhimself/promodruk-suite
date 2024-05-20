@extends("layouts.app")

@section("content")

<h1>Magazyn</h1>

<form action="{{ route("go-to-stock") }}" method="POST">
    @csrf
    <label for="product_code">Podaj kod produktu</label>
    <input type="text" name="product_code" id="product_code">

    <button type="submit">Przejdź</button>
</form>

@endsection
