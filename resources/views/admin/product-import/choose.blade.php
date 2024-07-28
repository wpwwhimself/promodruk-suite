@extends("layouts.admin")
@section("title", "Importuj produkt dostawcy")

@section("content")

<form action="{{ route('products-import-choose') }}" method="post">
    @csrf

    <h2>
        Znalezione produkty
        <small class="ghost">{{ $product_code }}</small>
    </h2>

    <style>
    .table {
        --col-count: 4;
        grid-template-columns: repeat(var(--col-count), auto);
    }
    </style>
    <div class="table">
        <span class="head">Kod</span>
        <span class="head">Nazwa</span>
        <span class="head">Kolor</span>
        <hr>

        @forelse ($data as $row)
        <span>{{ $row["code"] }}</span>
        <span>
            <img src="{{ $row["image_url"][0] }}" alt="{{ $row["name"] }}" class="inline">
            {{ $row["name"] }}
        </span>
        <span>{{ $row["variant_name"] }}</span>

        <button type="submit" name="product_code" value="{{ $row["code"] }}">Wybierz</button>

        @empty
        <span class="ghost" style="grid-column: 1 / span 5">
            Nie udało się znaleźć produktu o kodzie {{ $product_code }}
        </span>
        @endforelse
    </div>

    @if ($data)
    <button type="submit" name="product_code" value="{{ $product_code }}">Wybierz wszystkie</button>
    @endif

</form>

@endsection
