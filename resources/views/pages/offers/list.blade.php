@extends("layouts.app")
@section("title", "Oferty")

@section("content")

<x-app.section title="Lista ofert">
    <x-slot:buttons>
        <a class="button" href="{{ route("offers.offer") }}">Utwórz nową</a>
    </x-slot:buttons>

    <div class="table" style="--col-count: 4;">
        <span class="head">Nazwa</span>
        <span class="head">Twórca</span>
        <span class="head">Data utworzenia</span>
        <span class="head"></span>

        <hr>

        @forelse ($offers as $offer)
        <span>{{ $offer->name }}</span>
        <span>{{ $offer->creator->name }}</span>
        <span>{{ $offer->created_at->diffForHumans() }}</span>
        <span>
            <a href="{{ route("documents.offer", $offer->id) }}">Pobierz</a>
        </span>
        @empty
        <span class="ghost" style="grid-column: 1 / span var(--col-count);">
            Brak utworzonych ofert
        </span>
        @endforelse
    </div>
</x-app.section>
@endsection
