@extends("layouts.admin")
@section("title", "Synchronizacje")

@section("content")

<x-magazyn-section title="Lista integratorów">
    <x-slot:buttons>
        <span class="button" onclick="setSync('reset')">Resetuj wszystkie</a>
    </x-slot:buttons>

    <style>
    .table {
        --col-count: 9;
        grid-template-columns: repeat(var(--col-count), auto);
    }
    </style>

    <div class="table">
        <span class="ghost">Ładowanie...</span>
    </div>
</x-magazyn-section>

<script>
const fetchData = () => {
    fetch("/api/synchronizations")
        .then(res => res.json())
        .then(data => {
            const table = document.querySelector(".table")
            output = data.map(sync =>
                `<span>${sync.supplier_name}</span>
                <span class="button"
                    onclick="setSync('enable', '${sync.supplier_name}', 'product', ${(!sync.product_import_enabled).toString()})"
                >
                    ${sync.product_import_enabled ? `<span class=\"success\">Włączona</span>` : `<span class="danger">Wyłączona</span>`}
                </span>
                <span class="button"
                    onclick="setSync('enable', '${sync.supplier_name}', 'stock', ${!(sync.stock_import_enabled).toString()})"
                >
                    ${sync.stock_import_enabled ? `<span class=\"success\">Włączona</span>` : `<span class="danger">Wyłączona</span>`}
                </span>
                <span class="button"
                    onclick="setSync('enable', '${sync.supplier_name}', 'marking', ${!(sync.marking_import_enabled).toString()})"
                >
                    ${sync.marking_import_enabled ? `<span class=\"success\">Włączona</span>` : `<span class="danger">Wyłączona</span>`}
                </span>
                <span class="${sync.status[1]}">${sync.status[0]}</span>
                <span>${sync.progress}%</span>
                <span>${sync.current_external_id ?? ""}</span>
                <span>${sync.last_sync_started_at ?? ""}</span>
                <span class="button"
                    onclick="setSync('reset', '${sync.supplier_name}')"
                >
                    Resetuj
                </span>`)
                .join("")

            const sync_toggle_statuses = {
                product: data.reduce((acc, sync) => sync.product_import_enabled ? acc + 1 : acc, 0),
                stock: data.reduce((acc, sync) => sync.stock_import_enabled ? acc + 1 : acc, 0),
                marking: data.reduce((acc, sync) => sync.marking_import_enabled ? acc + 1 : acc, 0),
            }

            table.innerHTML =
                `<span class="head">Dostawca</span>
                <span class="head button" onclick="setSync('enable', null, 'product', ${(sync_toggle_statuses.product == 0).toString()})">
                    Synch. produktów
                </span>
                <span class="head button" onclick="setSync('enable', null, 'stock', ${(sync_toggle_statuses.stock == 0).toString()})">
                    Synch. stanów mag.
                </span>
                <span class="head button" onclick="setSync('enable', null, 'marking', ${(sync_toggle_statuses.marking == 0).toString()})">
                    Synch. znakowań
                </span>
                <span class="head">Status</span>
                <span class="head">Postęp</span>
                <span class="head">Obecne ID</span>
                <span class="head">Ostatni import</span>
                <span class="head">Akcje</span>
                <hr>`
                + output
        })
}

const setSync = (func_name, supplier_name = null, mode = null, enabled = null) => {
    fetch(`/api/synchronizations/${func_name}`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Content-Type": "application/json",
            "Accept": "application/json",
        },
        body: JSON.stringify({
            supplier_name: supplier_name,
            mode: mode,
            enabled: enabled
        })
    })
}

fetchData()
setInterval(fetchData, 1.5e3)
</script>

@endsection
