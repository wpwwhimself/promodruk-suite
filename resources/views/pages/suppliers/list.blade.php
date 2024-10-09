@extends("layouts.app")
@section("title", "Ustawienia dostawców")

@section("content")

<x-app.section title="Lista dostawców">
    <x-slot:buttons>
        <a class="button" href="{{ route("suppliers.edit") }}">Dodaj nowego</a>
    </x-slot:buttons>

    <div class="table" style="--col-count: 3;">
        <span class="head">Nazwa</span>
        <span class="head">Możliwe rabaty</span>
        <span class="head"></span>

        <hr>

        @foreach ($suppliers as $supplier)
        <span>{{ $supplier->name }}</span>
        <span>{{ count($supplier->allowed_discounts ?? []) }}</span>
        <span>
            <a href="{{ route("suppliers.edit", $supplier->id) }}">Edytuj</a>
        </span>
        @endforeach
    </div>
</x-app.section>
@endsection
