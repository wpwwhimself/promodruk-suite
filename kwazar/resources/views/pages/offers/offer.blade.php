@extends("layouts.shipyard.admin")
@section("title", $offer?->name ?? "Nowa oferta")
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

function addQuantityFromMaker(e, input) {
    if (e.type === "keydown" && e.which !== 13) return;
    e.preventDefault();
    if (input.value) _appendQuantity(input, input.value);
    revealAddButton(input.closest('.section'))
    input.value = null;
}

let _appendQuantity = (input, quantity) => {
    input.closest(".section").querySelector(".quantities").insertAdjacentHTML("beforeend",
        `<div {{ Popper::pop("Usuń ilość") }} onclick="section = this.closest('.section'); this.remove(); revealAddButton(section);">
            <input type="hidden" name="quantities[${input.dataset.product}][]" value="${quantity}">
            <span class="button">${quantity}</span>
        </div>`
    )
}

let quantities = {}

const showQuantities = (section) => {
    const quantitiesButtons = section.querySelector(".quantities");
    quantitiesButtons.parentElement.classList.toggle("hidden");
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

// init quantities
function initFieldsOnTheList() {
    let quantities = [];
    document.querySelectorAll(`input[id^='quantities_maker']`).forEach(qm => {
        const product_id = qm.dataset.product;
        const qtys = qm.closest(`[role='quantities']`).querySelector("strong").textContent.split("/");
        quantities[product_id] = qtys;
    });
    Object.keys(quantities).forEach(product_id => {
        quantities[product_id].forEach(qty => {
            if (!qty) return;
            _appendQuantity(document.querySelector(`input[id="quantities_maker[${product_id}]"]`), qty);
        })
    })
}

// init global surcharge (if no products available, show default for user)
// document.querySelector("input[name=global_surcharge]").value = " $user->global_surcharge ";

function resetGlobalSurcharge() {
    document.querySelector(`input[name="global_surcharge"]`).value = null;
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
            main_form.action = "{{ route('offers.save') }}"
            main_form.submit()
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
        <x-shipyard.app.card title="Konfiguracja" icon="cog">
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
                <x-shipyard.ui.button
                    label="Dodaj produkt"
                    icon="plus"
                    class="tertiary"
                    action="none"
                    onclick="openOfferModal('add-product')"
                />

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
                    </div>
                </div>
                <div style="flex-direction: column;">
                    <label>Pokaż stany mag.:</label>
                    <div>
                        <x-shipyard.ui.input
                            type="checkbox"
                            name="show_stocks" label="Dla danego war. na górze"
                            value="1"
                            :checked="$offer?->stocks_visible"
                            class="small compact"
                        />
                        <x-shipyard.ui.input
                            type="checkbox"
                            name="show_stocks_per_variant" label="Dla wszystkich war. na dole"
                            value="1"
                            :checked="$offer?->stocks_per_variant_visible"
                            class="small compact"
                        />
                    </div>
                </div>
            </div>

            <div id="discounts-wrapper" class="hidden flex right center">
                <x-user.discounts :user="Auth::user()" field-name="discounts" />
            </div>
        </x-shipyard.app.card>

        <x-shipyard.app.card title="Statystyki" icon="abacus" class="flex down">
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

<div id="offer-modal" class="modal hidden">
    <x-shipyard.app.loader />

    <x-shipyard.app.card title="..." title-lvl="2" id="modal-card" class="hidden">
        <div role="fields"></div>

        <div class="flex right center middle">
            <x-shipyard.ui.button
                icon="close"
                pop="Zamknij"
                action="none"
                onclick="closeOfferModal()"
                class="tertiary"
                role="close_modal"
            />
        </div>
    </x-shipyard.app.card>
</div>

<script>
const main_form = document.forms[0];

const offer_modal = document.querySelector("#offer-modal");
const offer_loader = offer_modal.querySelector(".loader");
const offer_card = offer_modal.querySelector("#modal-card");
const offer_card_loader = offer_card.querySelector(".loader");
const offer_fields = offer_card.querySelector("[role='fields']");

const offer_close_modal_btn = offer_card.querySelector("[role='close_modal']");

function openOfferModal(mode, defaults = {}, overrides = {}) {
    closeOfferModal();
    offer_loader.classList.remove("hidden");
    offer_modal.classList.remove("hidden");

    switch (mode) {
        case "submit":
            fetch(main_form.action, {
                method: main_form.method,
                body: new FormData(main_form)
            })
                .then(res => res.text())
                .then(res => {
                    document.querySelector("#positions").innerHTML = res;
                })
                .catch(err => {
                    console.error(err);
                })
                .finally(() => {
                    closeOfferModal();
                    reapplyPopper();
                    reinitSelect();
                    updateStats();
                    initFieldsOnTheList();
                });
            break;

        case "add-product":
            offer_card.querySelector("[role$='title']").textContent = "Dodaj produkt";
            offer_fields.insertAdjacentHTML("beforeend",
                `<x-shipyard.ui.input type="lookup"
                    name='search'
                    label='Szukaj'
                    icon='magnify'
                    :select-data="[
                        'dataUrl' => env('MAGAZYN_API_URL') . 'products/for-markings',
                        'dataParams' => [
                            'suppliers' => $suppliers->pluck('name'),
                        ],
                    ]"
                />
                <div id="search-results"></div>`
            );

            offer_card.classList.remove("hidden");
            reapplyPopper();
            reinitSelect();
            offer_fields.querySelector("#search").focus();

            offer_loader.classList.add("hidden");
            break;
    }
}

function closeOfferModal() {
    offer_fields.innerHTML = "";
    offer_card.classList.add("hidden");
    offer_modal.classList.add("hidden");

    offer_close_modal_btn.classList.remove("hidden");
}

function submitWithLoader() {
    openOfferModal("submit");
}

function searchProducts(q) {
    if (q.length < 3) return;

    offer_card_loader.classList.remove("hidden");

    const params = new URLSearchParams({q: q});
    {!! json_encode($suppliers->pluck("name")) !!}.forEach(supplier => {
        params.append("suppliers[]", supplier);
    });

    fetch(`{{ env('MAGAZYN_API_URL') }}products/for-markings?` + params)
        .then(res => res.json())
        .then(res => {
            const rows = res.results.map(product =>
                `<tr data-id="${product.id}">
                    <td class="ghost">${product.id}</td>
                    <td>${product.text}</td>
                    <td>${product.stock} szt.</td>
                    <td>
                        <x-shipyard.ui.button
                            icon="arrow-right"
                            pop="Wybierz"
                            action="none"
                            onclick="addProductToOffer(this)"
                        />
                    </td>
                </tr>`
            ).join("");
            offer_fields.querySelector("#search-results").innerHTML = `<table><tbody>${rows}</tbody></table>`;
        })
        .catch(err => {
            console.error(err);
            offer_fields.querySelector("#search-results").innerHTML = "<span class='accent error'>Nie udało się pobrać produktów</span>";
        })
        .finally(() => {
            offer_card_loader.classList.add("hidden");
            reapplyPopper();
        });
}

function addProductToOffer(btn) {
    main_form.insertAdjacentHTML("beforeend", `<input type="hidden" name="product" value="${btn.closest("tr").dataset.id}" />`);
    submitWithLoader();
}

initFieldsOnTheList();
</script>

<style>
input[type=number] {
    /* width: 4.5em; */
}
.grid {
    gap: 0;
}
</style>

@endsection
