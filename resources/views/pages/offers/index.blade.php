@extends("layouts.app")
@section("title", "Przygotuj ofertę")

@section("content")

<form action="{{ route('offers.prepare') }}" method="post" class="flex-down">
    @csrf

    <section>
        <x-multi-input-field
            name="product"
            label="Dodaj produkt do listy"
            empty-option="Wybierz..."
            :options="[]"
        />

        <div id="chosen_products"></div>
    </section>

    <section>
        <x-input-field
            type="number"
            name="quantity"
            label="Dodaj ilości"
            min="1"
            step="1"
        />

        <div id="chosen_quantities"></div>
    </section>

    <section class="flex-right center middle">
        <button type="submit">Przygotuj wycenę</button>
    </section>
</form>

<script defer>
$(document).ready(function() {
    $("select#product").select2({
        ajax: {
            url: "{{ env('MAGAZYN_API_URL') }}products/for-markings",
            data: (params) => ({
                q: params.term,
            }),
        }
    }).on("select2:select", function(e) {
        $("#chosen_products").append(
            `<div class="flex-right stretch middle">
                <input type="hidden" name="product_ids[]" value="${e.params.data.id}">
                <span>${e.params.data.text}</span>
                <span class="button" onclick="this.parentElement.remove()">×</span>
            </div>`
        );

        $("select#product").val(null).trigger("change");
    })

    $("input#quantity").keydown(function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            $("#chosen_quantities").append(
            `<div class="flex-right stretch middle">
                <input type="hidden" name="quantities[]" value="${e.target.value}">
                <span>${e.target.value}</span>
                <span class="button" onclick="this.parentElement.remove()">×</span>
            </div>`
            );

            $("input#quantity").val(null);
        }
    })
})
</script>

@endsection
