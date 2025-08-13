@extends("layouts.app")
@section("title", "Przetworzone oferty")

@section("content")

<x-app.section title="Lista plików">
    <x-slot:buttons>
        <a class="button" href="{{ route('documents.offers.delete') }}">Wyczyść</a>
    </x-slot:buttons>

    <p>
        Kolejka pobierania plików co {{ App\Models\OfferFile::WORKER_DELAY_MINUTES }} minut podejmuje się pobrania kolejnego dużego pliku.
    </p>

    <table>
        <thead>
            <tr>
                <th>Nazwa</th>
                <th>Format</th>
                <th>Pobrany</th>
                <th>Data modyfikacji</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($files as $file)
            <tr>
                <td>{{ $file->offer->name }}</td>
                <td>{{ $file->type }}</td>
                @if ($file->file_path)
                <td class="success">Tak</td>
                @else
                <td class="error">Nie</td>
                @endif
                <td {{ Popper::pop($file->updated_at) }}>{{ $file->updated_at->diffForHumans() }}</td>
                <td>
                    <a href="{{ route("offers.offer", $file->offer->id) }}">Edytuj</a>
                    @if ($file->file_path) <a href="{{ route("documents.offer", ["format" => $file->type, "id" => $file->offer->id]) }}">Pobierz</a> @endif
                    <a href="{{ route("documents.offers.delete", ["file" => $file]) }}">Usuń</a>
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

    {{ $files->links() }}
</x-app.section>

<script defer>
setTimeout(() => {
    window.location.reload()
}, 10e3)
</script>
@endsection
