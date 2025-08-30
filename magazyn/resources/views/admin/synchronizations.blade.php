@extends("layouts.admin")
@section("title", "Synchronizacje")

@section("content")

<x-magazyn-section title="Lista integratorów">
    <x-slot:buttons>
        <span class="button" onclick="setSync('reset')">Resetuj wszystkie</span>
        <span class="button" onclick="setSync('enable', null, null, false)">Wyłącz wszystkie</span>
    </x-slot:buttons>

    <div class="table">
        <span class="ghost">Ładowanie...</span>
    </div>
</x-magazyn-section>

<div class="grid" style="--col-count: 2;">
    <x-magazyn-section title="Kolejka synchronizacji">
        <div class="queue">
            <span class="ghost">Ładowanie...</span>
        </div>
    </x-magazyn-section>

    <x-magazyn-section title="Logi">
        <div class="logs">
            <span class="ghost">Ładowanie...</span>
        </div>
    </x-magazyn-section>
</div>

<script>
const fetchData = () => {
    fetch("/api/synchronizations")
        .then(res => res.json())
        .then(({ table, queue, logs }) => {
            document.querySelector(".table").innerHTML = table;
            document.querySelector(".queue").innerHTML = queue;
            document.querySelector(".logs").innerHTML = logs;
        });
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
setInterval(fetchData, 3e3)
</script>

@endsection
