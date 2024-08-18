@props([
    "productId",
    "long" => false,
    "highlightId" => null,
])

<div class="stock-display">
    {{-- <h3>Aktualny stan magazynowy</h3> --}}
    <span class="loader">Ładowanie...</span>
</div>

<script defer>
fetch(`{{ env('MAGAZYN_API_URL') }}stock/{{ $productId }}/1`)
    .then(res => res.json())
    .then(data => {
        document.querySelector(".stock-display .loader").remove()

        data.forEach(row => {
            document.querySelector(".stock-display")
                .append(fromHTML(`<span class="grid ${row.id == "{{ $highlightId }}" ? 'accent' : ''}" style="grid-template-columns: 1fr 2fr;">
                    <span>
                        Kolor <a href="/produkty/${row.id}">${row.original_color_name.toLocaleLowerCase("pl")}</a>:
                        <b>${row.current_stock} szt.</b>,
                    </span>
                    <span>
                        Przewid. dost.: ${row.future_delivery_amount ? `${row.future_delivery_amount} szt., ${row.future_delivery_date}` : "brak"}
                    </span>
                </span>`))
        })
    })
</script>

<style>
.stock-display {
    margin-block: 0.5em;
}
</style>
