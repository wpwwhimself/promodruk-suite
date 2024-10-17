@extends("layouts.admin")
@section("title", "Synchronizacje")

@section("content")

<x-magazyn-section title="Lista integratorów">
    <x-slot:buttons>
        <a class="button" href="{{ route("synch-reset") }}">Resetuj wszystkie</a>
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
                <a href="/admin/synchronizations/enable/${sync.supplier_name}/product/${!sync.product_import_enabled * 1}">
                    ${sync.product_import_enabled ? `<span class=\"success\">Włączona</span>` : `<span class="danger">Wyłączona</span>`}
                </a>
                <a href="/admin/synchronizations/enable/${sync.supplier_name}/stock/${!sync.stock_import_enabled * 1}">
                    ${sync.stock_import_enabled ? `<span class=\"success\">Włączona</span>` : `<span class="danger">Wyłączona</span>`}
                </a>
                <a href="/admin/synchronizations/enable/${sync.supplier_name}/marking/${!sync.marking_import_enabled * 1}">
                    ${sync.marking_import_enabled ? `<span class=\"success\">Włączona</span>` : `<span class="danger">Wyłączona</span>`}
                </a>
                <span class="${sync.status[1]}">${sync.status[0]}</span>
                <span>${sync.progress}%</span>
                <span>${sync.current_external_id ?? ""}</span>
                <span>${sync.last_sync_started_at ?? ""}</span>
                <span>
                    <a href="/admin/synchronizations/reset/${sync.supplier_name}">Resetuj</a>
                </span>`)
                .join("")

            table.innerHTML =
                `<span class="head">Dostawca</span>
                <span class="head">Synch. produktów</span>
                <span class="head">Synch. stanów mag.</span>
                <span class="head">Synch. znakowań</span>
                <span class="head">Status</span>
                <span class="head">Postęp</span>
                <span class="head">Obecne ID</span>
                <span class="head">Ostatni import</span>
                <span class="head">Akcje</span>
                <hr>`
                + output
        })
}

fetchData()
setInterval(fetchData, 2e3)
</script>

@endsection
