@extends("layouts.admin")
@section("title", "Importuj produkt dostawcy")

@section("content")

<form action="{{ route('products-import-choose') }}" method="post">
    @csrf
    <input type="hidden" name="query" value="{{ $product_code }}">

    <h2>
        Znalezione produkty
        <small class="ghost">{{ $product_code }}</small>
    </h2>

    <style>
    .table {
        --col-count: 5;
        grid-template-columns: repeat(var(--col-count), auto);
    }
    </style>
    <div class="table">
        <span class="head">Kod</span>
        <span class="head">Nazwa</span>
        <span class="head">Kolor</span>
        <span class="head">Cecha podst.</span>
        <input class="head" type="checkbox" onchange="selectAll(event.target.checked)">
        <hr>

        @forelse ($data as $row)
        <span>{{ $row["code"] }}</span>
        <span>
            <img src="{{ $row["image_url"][0] }}" alt="{{ $row["name"] }}" class="inline">
            {{ $row["name"] }}
        </span>
        <span>{{ $row["variant_name"] }}</span>

        <span class="flex-right">
            <x-multi-input-field name="main_attributes[{{ $row['code'] }}]"
                label=""
                :options="$mainAttributes"
                empty-option="brak"
                onchange="changeMainAttributeColor(event.target.value, '{{ $row['code'] }}')"
            />
            <x-color-tag color="" data-id="{{ $row['code'] }}" />
        </span>

        <input type="checkbox" name="product_codes[]" value="{{ $row["code"] }}">

        @empty
        <span class="ghost" style="grid-column: 1 / span 5">
            Nie udało się znaleźć produktu o kodzie {{ $product_code }}
        </span>
        @endforelse
    </div>

    <script>
    const changeMainAttributeColor = (attr_id, code) => {
        fetch(`/api/main-attributes/${attr_id}`).then(res => res.json()).then(attr => {
            document.querySelector(".color-tile[data-id=" + code + "]").style = `--tile-color: ${attr.color}`
        })
    }
    </script>

    @if ($data)
    <button type="submit">Importuj</button>
    @endif

</form>

<script>
const selectAll = (val) => {
    document.querySelectorAll("input[name^=product_codes]").forEach(el => el.checked = val)
}
</script>

@endsection
