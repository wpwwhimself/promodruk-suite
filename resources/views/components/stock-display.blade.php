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
                .append(fromHTML(`<span ${row.id == "{{ $highlightId }}" ? 'class="accent"' : ''}>
                    <a href="/produkty/${row.id}">${row.original_color_name}</a>:
                    <b>${row.current_stock} szt.</b>,
                    przewidywana dostawa: ${row.future_delivery_amount ? `${row.future_delivery_amount} szt., ${row.future_delivery_date}` : "brak"}
                </span>`))
        })
    })
</script>
