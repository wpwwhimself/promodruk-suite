@extends("layouts.app")
@section("title", "Oferty")

@section("content")

<x-app.section title="Lista ofert">
    <x-slot:buttons>
        <a class="button" href="{{ route("offers.offer") }}">Utwórz nową</a>
    </x-slot:buttons>

    <table>
        <thead>
            <tr>
                <th>Nazwa</th>
                <th>Twórca</th>
                <th>Pozycji</th>
                <th>Data utworzenia</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($offers as $offer)
            <tr>
                <td>{{ $offer->name }}</td>
                <td>{{ $offer->creator->name }}</td>
                <td>{{ count($offer->positions) }}</td>
                <td {{ Popper::pop($offer->created_at) }}>{{ $offer->created_at->diffForHumans() }}</td>
                <td>
                    <a href="{{ route("offers.offer", $offer->id) }}">Edytuj</a>
                    <a href="{{ route("documents.offer", $offer->id) }}">Pobierz</a>
                </td>
            </tr>
            @empty
            <tr>
                <td class="ghost">
                    Brak utworzonych ofert
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{ $offers->links() }}
</x-app.section>
@endsection
