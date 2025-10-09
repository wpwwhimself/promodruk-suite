@extends("layouts.app")
@section("title", implode(" | ", [$supplier->name ?? "Nowy dostawca", "Edycja dostawcy"]))

@section("content")

<form action="{{ route("suppliers.process") }}" method="POST" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $supplier?->id }}">

    <div class="grid" style="--col-count: 2;">
        @if (!$supplier)
        <x-app.section title="Dane dostawcy" class="flex-down">
            <x-multi-input-field
                name="name"
                label="Wybierz dostawcę"
                :options="$available_suppliers"
                empty-option="Wybierz..."
            />
        </x-app.section>
        @endif

        <x-app.section title="Możliwe rabaty" class="flex-down">
            @foreach ($allowed_discounts as $label => $name)
            <x-input-field type="checkbox"
                name="allowed_discounts[]"
                :label="$label"
                :value="$name"
                :checked="in_array($name, $supplier?->allowed_discounts ?? [])"
            />
            @endforeach
        </x-app.section>

        @if ($supplier)
        <x-app.section title="Niestandardowe rabaty dla produktów" class="flex-down">
            <p>
                Tutaj możesz określić rabaty, jakie będą przyjmowane dla konkretnych rodzin produktów.
                Funkcja ta może być przydatna w przypadku wykluczania rabatowania produktów, których ceny dostawcy nie zawierają rabatu.
            </p>

            <div class="flex-right center middle">
                <x-multi-input-field
                    name="product"
                    label="Dodaj produkt do listy"
                    empty-option="Wybierz..."
                    :options="[]"
                />

                <x-input-field type="number"
                    name="custom_discount"
                    label="Rabat (%)"
                />

                <span class="button" onclick="addCustomDiscount()">Dodaj</span>
            </div>

            <ul role="custom-discounts"></ul>

            <script>
            $("select#product").select2({
                ajax: {
                    url: "{{ env('MAGAZYN_API_URL') }}products/for-custom-discounts",
                    delay: 250,
                    data: (params) => ({
                        q: params.term,
                        suppliers: {!! json_encode($available_suppliers->values()) !!}
                    }),
                },
                width: "20em",
                minimumInputLength: 2,
            });

            function addCustomDiscount(data = undefined) {
                const family_id = data ? data["family_id"] : $("select#product").val();
                const family_name = data ? data["family_name"] : $("select#product").find(":selected").text();
                const discount = data ? data["discount"] : $("input[name=custom_discount]").val();

                if (!family_id || !discount) return;

                data = JSON.stringify({
                    "family_id": family_id,
                    "family_name": family_name,
                    "discount": discount
                }).replace(/"/g, "&quot;");

                document.querySelector("[role='custom-discounts']").insertAdjacentHTML("beforeend", `
                    <li>
                        <input type="hidden" name="custom_discounts[]" value="${data}">
                        <a href="{{ env('MAGAZYN_API_URL') }}admin/products/edit-family/${family_id}" target="_blank">
                            ${family_name}
                        </a>: ${discount}%
                        <span class="button" onclick="removeCustomDiscount(this)">Usuń</span>
                    </li>
                `);

                $("select#product").val(null).trigger("change");
                $("input[name=custom_discount]").val(null);
            }

            function removeCustomDiscount(btn) {
                btn.closest("li").remove();
            }

            @foreach ($supplier->custom_discounts ?? [] as $d)
            addCustomDiscount({!! json_encode($d) !!});
            @endforeach
            </script>
        </x-app.section>
        @endif
    </div>

    <div class="section flex-right center middle">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($supplier) <button type="submit" name="mode" value="delete" class="danger">Usuń</button> @endif
    </div>
</form>

@endsection
