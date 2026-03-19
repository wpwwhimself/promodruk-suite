@extends("layouts.shipyard.admin")
@section("title", $offer->name)
@section("subtitle", "Szczegóły oferty")

@section("content")

<script>
//?// stats //?//
const updateStats = () => {
    document.querySelector("[role='stats-products-count']").textContent = document.querySelectorAll("input[name^='product_ids']").length;
}

//?// discounts //?//

const toggleDiscounts = (btn) => {
    document.querySelector("#discounts-wrapper").classList.toggle("hidden")
    btn.classList.toggle("active")
}

//?// marking filters //?//

const filterMarkingsForPosition = (input) => {
    const markings_container = input.closest("[role='markings']");
    const current_filters = Array.from(markings_container.querySelectorAll("[role='marking-filters'] select")).map(input => input.value).join("|");

    markings_container.querySelectorAll(".offer-position").forEach(row => {
        row.classList.toggle("hidden", !row.dataset.query.includes(current_filters) || current_filters == "|");
    });
}

//?// quantities //?//

let _appendQuantity = (input, quantity) => {
    input.closest("section").find(".quantities").append(`<div {{ Popper::pop("Usuń ilość") }} onclick="section = this.closest('section'); this.remove(); revealAddButton(section);">
        <input type="hidden" name="quantities[${input.attr("data-product")}][]" value="${quantity}">
        <span class="button">${quantity}</span>
    </div>`)
}

let quantities = {}

const showQuantities = (section) => {
    section.querySelector(".quantities").parentElement.classList.toggle("hidden")
}

const revealAddButton = (section) => {
    const quantities = section.querySelector(".quantities").children
    const addBtn = section.querySelector(".button[role='add-button']").classList.toggle("hidden", !quantities.length)
}

const makeEditable = (section) => {
    const edited_btn = section.querySelector(".button[role='edit-button']")
    const edited_input = section.querySelector("input[name='edited[]']")
    edited_input.checked = !edited_input.checked
    edited_btn.textContent = edited_input.checked ? "Zamknij" : "Edytuj"
    section.querySelector("[role='prices']").classList.toggle("hidden")
}

const deleteProductFromOffer = (section) => {
    section.remove()
    updateStats();
}

//?// calculations //?//
const openCalculationsPopup = (product_id, availableCalculations, code, field = 'items') => {
    toggleDialog(
        "Wybierz kalkulację",
        [...availableCalculations, "new"]
            .map((calc) => `<span class="button" role="add-calculation" data-calc-id="${calc}"
                onclick="addCalculation('${product_id}', '${calc}', '${code}', '${field}')"
            >
                ${calc == "new" ? "Nowa kalkulacja" : `Kalkulacja nr ${calc + 1}`}
            </span>`)
            .join("")
    )
}

const addCalculation = (product_id, calculation, code, field = 'items') => {
    const container = document.querySelector(`.calculations[data-product-id="${product_id}"]`)
    calculation = (calculation == "new") ? container.dataset.count : calculation
    container.append(fromHTML(`<input type="hidden" name="calculations[${product_id}][${calculation}][${field}][][code]" value="${code}" />`))
    container.scrollIntoView({
        behavior: "smooth",
        block: "center",
    })
    toggleDialog()
    submitWithLoader()
}

const deleteCalculation = (product_id, calc_id, code, field = 'items') => {
    document.querySelector(`input[name^="calculations[${product_id}][${calc_id}][${field}]"][value="${code}"]`).remove()
    submitWithLoader()
}

//?// save offer //?//
const prepareSaveOffer = () => {
    toggleDialog(
        "Zapisz ofertę",
        `<x-input-field type="text"
            name="offer_name" label="Nazwa oferty"
            :value="$offer?->name"
            required
        />
        <x-input-field type="TEXT"
            name="offer_notes" label="Notatki"
            :value="$offer?->notes"
        />`,
        function() {
            form.action = "{{ route('offers.save') }}"
            form.submit()
        }
    )
}
</script>

<form action="{{ route('offers.prepare') }}" method="post"
    class="flex down"
    onsubmit="event.preventDefault(); submitWithLoader()"
>
    @csrf
    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
    <input type="hidden" name="offer_id" value="{{ $offer?->id }}">

    <x-app.loader text="Przeliczanie" />
    <x-app.dialog title="Wybierz kalkulację" />

    <div class="flex right spread and-cover sticky">
        <x-shipyard.app.card title="Konfiguracja">
            <x-slot:actions>
                <x-shipyard.ui.button
                    action="submit"
                    label="Przelicz ofertę"
                />
                <x-input-field type="checkbox"
                    name="remember_missing" label="Pozostaw usunięte"
                    class="small compact"
                    value="1"
                    :checked="true"
                />
                <x-shipyard.ui.button
                    class="primary"
                    action="none"
                    onclick="prepareSaveOffer()"
                    label="Zapisz i zakończ"
                />
            </x-slot:actions>

            <div class="flex right center middle nowrap">
                <div>
                    <x-multi-input-field
                        name="product"
                        label="Dodaj produkt do listy"
                        empty-option="Wybierz..."
                        :options="[]"
                    />
                </div>

                <div class="flex right center middle nowrap">
                    <x-shipyard.ui.button
                        action="none"
                        class="toggle"
                        label="Rabaty"
                        onclick="toggleDiscounts(this)"
                    />
                    <x-input-field type="number"
                        name="global_surcharge" label="Nadwyżka (%)"
                        min="0" step="0.1"
                    />
                </div>

                <div style="flex-direction: column;">
                    <label>Pokaż:</label>
                    <div>
                        <x-shipyard.ui.input
                            type="checkbox"
                            name="show_prices_per_unit" label="Ceny/szt."
                            value="1"
                            :checked="$offer?->unit_cost_visible"
                            onchange="submitWithLoader()"
                            class="small compact"
                        />
                        <x-shipyard.ui.input
                            type="checkbox"
                            name="show_gross_prices" label="Ceny brutto"
                            value="1"
                            :checked="$offer?->gross_prices_visible"
                            onchange="submitWithLoader()"
                            class="small compact"
                        />
                        <x-shipyard.ui.input
                            type="checkbox"
                            name="show_stocks" label="Stany mag. na wydruku"
                            value="1"
                            :checked="$offer?->stocks_visible"
                            class="small compact"
                        />
                    </div>
                </div>
            </div>

            <div id="discounts-wrapper" class="hidden flex right center">
                <x-user.discounts :user="Auth::user()" field-name="discounts" />
            </div>
        </x-shipyard.app.card>

        <x-shipyard.app.card title="Statystyki" class="flex down">
            <ul class="flashy-list">
                <li>Produktów w ofercie: <strong role="stats-products-count">{{ count($offer?->positions ?? []) }}</strong></li>
            </ul>
        </x-shipyard.app.card>
    </div>

    <div id="positions" class="flex down">
        @if ($offer?->positions)
        <x-offer.position-list
            :products="collect($offer->positions)"
            :user="Auth::user() ?? User::find($rq->user_id)"
            :show-prices-per-unit="$offer?->unit_cost_visible"
            :show-gross-prices="$offer?->gross_prices_visible"
            :show-stocks="true"
        />
        @endif
    </div>
</form>

<script defer>
const form = document.forms[0]
const submitWithLoader = () => {
    toggleLoader()
    fetch(form.action, {
        method: form.method,
        body: new FormData(form)
    })
        .then(res => res.text())
        .then(res => {
            toggleLoader()
            document.querySelector("#positions").innerHTML = res;
        })
        .catch(err => {
            console.error(err);
        });
}

$("select#product").select2({
    ajax: {
        url: "{{ env('MAGAZYN_API_URL') }}products/for-markings",
        delay: 250,
        data: (params) => ({
            q: params.term,
            suppliers: {!! json_encode($suppliers->pluck("name")) !!}
        }),
    },
    width: "20em",
    minimumInputLength: 2,
}).on("select2:select", function(e) {
    submitWithLoader()
    $(this).val(null).trigger("change")
})
</script>

<style>
input[type=number] {
    width: 4.5em;
}
.grid {
    gap: 0;
}
</style>

@endsection
