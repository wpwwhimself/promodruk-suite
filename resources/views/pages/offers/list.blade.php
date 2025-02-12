@extends("layouts.app")
@section("title", "Oferty")

@section("content")

<x-app.section title="Lista ofert">
    <x-slot:buttons>
        <a class="button" href="{{ route("documents.offers")}}">Przetworzone</a>
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
                    @foreach ($document_formats as $format)
                    <a href="{{ route("documents.offer", ["format" => $format, "id" => $offer->id]) }}">
                        Pobierz {{ Str::upper($format) }}
                        @php
                        $file = $offer->files?->firstWhere("type", $format)
                        @endphp
                        @if ($file?->file_path)
                        <span @popper(zapisany)>🗃️</span>
                        @elseif ($file)
                        <span @popper(w kolejce przetwarzania)>⌛</span>
                        @elseif (count($offer->positions) > $offer::FILE_QUEUE_LIMIT)
                        <span @popper(trafi do kolejki przetwarzania)>🐌</span>
                        @endif
                    </a>
                    @endforeach
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
