@extends("layouts.app")
@section("title", "Ustawienia dostawców")

@section("content")

<x-app.section title="Lista dostawców">
    <x-slot:buttons>
        <a class="button" href="{{ route("suppliers.edit") }}">Dodaj nowego</a>
    </x-slot:buttons>

    <table>
        <thead>
            <tr>
                <th>Nazwa</th>
                <th>Możliwe rabaty</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($suppliers as $supplier)
            <tr>
                <td>{{ $supplier->name }}</td>
                <td>{{ count($supplier->allowed_discounts ?? []) }}</td>
                <td>
                    <a href="{{ route("suppliers.edit", $supplier->id) }}">Edytuj</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</x-app.section>
@endsection
