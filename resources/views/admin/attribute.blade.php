@extends("layouts.admin")
@section("title", implode(" | ", [$attribute->name ?? "Nowa cecha", "Edycja cechy"]))

@section("content")

<form action="{{ route('update-attributes') }}" method="post">
    @csrf
    <input type="hidden" name="id" value="{{ $attribute?->id }}">

    <h2>Cecha</h2>

    <x-input-field type="text" label="Nazwa" name="name" :value="$attribute?->name" />
    <x-multi-input-field :options="$types" label="Typ" name="type" :value="$attribute?->type" />

    <h2>Warianty</h2>

    <table class="variants">
        <thead>
            <tr>
                <th>Nazwa</th>
                <th>Wartość</th>
                <th>Akcja</th>
            </tr>
        </thead>
        <tbody>
        @if ($attribute)
        @foreach ($attribute->variants as $variant)
            <tr id="variant-{{ $variant->id }}">
                <td><input type="text" name="variants[names][{{ $variant->id }}]" value="{{ $variant->name }}"></td>
                <td><input type="text" name="variants[values][{{ $variant->id }}]" value="{{ $variant->value }}"></td>
                <td><span class="clickable" onclick="deleteVariant(this)">Usuń</span></td>
            </tr>
        @endforeach
        @endif
        </tbody>
        <tfoot>
            <tr>
                <td><input type="text" name="variants[names][]"></td>
                <td><input type="text" name="variants[values][]"></td>
                <td><span class="clickable" onclick="addVariant(this)">Dodaj</span></td>
            </tr>
        </tfoot>
    </table>

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

<script>
const addVariant = (btn) => {
    // gather new variant data
    const name = btn.closest("tr").querySelector("input[name^='variants[names]']").value
    const value = btn.closest("tr").querySelector("input[name^='variants[values]']").value

    // add row
    document.querySelector(".variants tbody")
        .append(fromHTML(`<tr>
            <td><input name="variants[names][]" value="${name}"></td>
            <td><input name="variants[values][]" value="${value}"></td>
            <td><span class="clickable" onclick="deleteVariant(this)">Usuń</span></td>
        </tr>`))

    // clear adder
    btn.closest("tr").querySelectorAll("input").forEach(el => el.value = "");
}

const deleteVariant = (btn) => {
    btn.closest("tr").remove()
}
</script>

<style>
.variants {
    & .hidden {
        display: none;
    }
}
</style>

@endsection
