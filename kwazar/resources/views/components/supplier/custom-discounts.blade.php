@props([
    "data"
])

<p>
    Tutaj możesz określić rabaty, jakie będą przyjmowane dla konkretnych rodzin produktów.
    Funkcja ta może być przydatna w przypadku wykluczania rabatowania produktów, których ceny dostawcy nie zawierają rabatu.
</p>

<div class="flex down">
    <x-shipyard.ui.input type="lookup"
        name="product"
        label="Dodaj produkt do listy"
        placeholder="Wyszukaj..."
        :select-data="[
            'dataUrl' => env('MAGAZYN_API_URL') . 'products/for-custom-discounts',
            'dataParams' => ['source' => $data->name],
        ]"
    />

    <x-shipyard.ui.input type="number"
        name="custom_discount"
        label="Rabat (%)"
    />

    <x-shipyard.ui.button
        label="Dodaj"
        class="tertiary"
        action="none"
        onclick="addCustomDiscount()"
    />
</div>

<ul role="custom-discounts"></ul>

<script>
function addCustomDiscount(data = undefined) {
    const selectedProduct = document.querySelector(`input[type='radio'][name='product']:checked`);
    const family_id = data ? data["family_id"] : selectedProduct.value;
    const family_name = data ? data["family_name"] : selectedProduct.closest(".input-container").textContent.trim();
    const discount = data ? data["discount"] : document.querySelector(`input[name='custom_discount']`).value;

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

    document.querySelector(`input[name='product'][type="text"]`).value = null;
    document.querySelector(`#lookup-container[for="product"] [role="results"]`).innerHTML = "";
    document.querySelector(`input[name='custom_discount']`).value = null;
}

function removeCustomDiscount(btn) {
    btn.closest("li").remove();
}

@foreach ($data->custom_discounts ?? [] as $d)
addCustomDiscount({!! json_encode($d) !!});
@endforeach
</script>
