@props([
    "supplier",
    "editable" => true,
])

<x-magazyn-section :title="$editable ? $supplier->name : $supplier->supplier_name">
    @if ($editable)
    <x-slot:buttons>
        <x-button :action="route('suppliers-edit', ['id' => $supplier->id])" label="Edytuj" />
    </x-slot:buttons>
    @endif

    @if ($supplier->notes)
    <p>{{ $supplier->notes }}</p>
    @endif

    <div class="flex-right wrap">
        <span>
            <strong>Prefiks</strong>:
            @if ($editable)
            {{ $supplier->prefix }}
            @else
                @php
                $class = "App\\DataIntegrators\\".$supplier->supplier_name."Handler";
                $sync = new $class($supplier);
                $prefix = $sync->getPrefix();
                @endphp
            {{ is_array($prefix) ? implode("/", $prefix) : $prefix }}
            @endif
        </span>

        <span>
            <strong>Produktów</strong>:
            {{ $supplier->productFamilies->count() }}
        </span>
    </div>
</x-magazyn-section>
