@extends("layouts.admin")

@section("title", "Wyszukiwanie plików")

@section("content")

<form class="section flex-down">
    <p class="ghost">
        Ten panel pozwala na wyszukiwanie plików znajdujących się w repozytorium.
        Po wpisaniu hasła wyświetlone zostaną ścieżki do wszystkich plików o podobnej nazwie lub lokalizacji zawierającej wskazane hasło.
    </p>

    <x-input-field type="text" name="q" label="Szukaj" autofocus />

    <div class="flex-right center">
        <x-button action="submit" icon="search" label="Szukaj" />
    </div>
</form>

@if (request("q"))
<x-magazyn-section title="Wyniki wyszukiwania" class="flex-down">
    @forelse ($files as $file)
    <a class="flex-right middle" href="{{ route('files', ['path' => Str::contains($file, '/') ? Str::beforeLast($file, '/') : null]) }}">
        <img class="inline" src="{{ asset(Storage::url($file)) }}" {{ Popper::pop("<img class='thumbnail' src='".asset(Storage::url($file))."' />") }} />
        {{ $file }}
    </a>
    @empty
    <p class="ghost">Brak wyników</p>
    @endforelse
</x-magazyn-section>
@endif

<div class="section flex-right center">
    <x-button :action="route('files')" label="Wróć" />
</div>

@endsection
