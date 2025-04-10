@extends("layouts.admin")

@section("title", "Repozytorium plików")

@section("content")

<p class="ghost">
    Tutaj możesz umieszczać pliki – np. grafiki – które mają pojawić się na podstronach.
    Po wgraniu ich na serwer możesz je umieścić w treściach strony, korzystając z wygenerowanych linków.
</p>

<x-magazyn-section title="Zawartość folderu">
    <x-slot:buttons>
        <x-button :action="route('files-search')" label="Znajdź pliki" />
    </x-slot:buttons>

    <div class="flex-right middle">
        <div class="ghost">Jesteś tutaj:</div>

        <h3>{{ request('path', 'Katalog główny') }}</h3>

        @if (!in_array(request("path"), ["public", null]))
        <x-button :action="route('files', [
            'path' => Str::contains(request('path'), '/') ? Str::beforeLast(request('path'), '/') : null,
            'select' => request('select'),
        ])" icon="arrow-left" class="phantom" label=".." />
        @endif

        @foreach ($directories as $dir)
        <x-button :action="route('files', ['path' => $dir, 'select' => request('select')])" icon="folder" class="phantom" :label="Str::afterLast($dir, '/')" />
        @endforeach
    </div>

    <div class="flex-right">
        @forelse ($files as $file)
        <x-magazyn-section :title="Str::afterLast($file, '/')" class="flex-down middle">
            <img src="{{ Storage::url($file) }}" alt="{{ Str::afterLast($file, '/') }}" class="thumbnail">

            <div class="flex-right middle center">
                @if (request()->has("select"))
                <x-button icon="check" label="Wybierz" class="interactive" onclick="selectFile('{{ asset(Storage::url($file)) }}', '{{ request('select') }}')" />
                @else
                <x-button :action="route('files-download', ['file' => $file])" target="_blank" icon="download" label="Pobierz" class="phantom" />
                <span icon="link" class="button phantom interactive" onclick="copyToClipboard('{{ asset(Storage::url($file)) }}')">Link</span>
                <span class="button interactive" onclick="initFileReplace('{{ $file }}')">Podmień</span>
                <x-button :action="route('files-delete', ['file' => $file])" icon="delete" label="Usuń" class="danger" />
                @endif
            </div>
        </x-magazyn-section>
        @empty
        <p class="ghost">Brak plików</p>
        @endforelse
    </div>
</x-magazyn-section>

<div class="grid" style="--col-count: 2;">
    <x-magazyn-section title="Wgrywanie plików">
        <form action="{{ route('files-upload') }}" method="post" enctype="multipart/form-data" class="flex-down">
            @csrf
            <input type="hidden" name="path" value="{{ request("path") }}">
            <input type="hidden" name="force_file_name">
            <input type="file" name="files[]" id="files" multiple>

            <span class="ghost">Pliki zostaną zapisane w obecnie wybranym katalogu.</span>

            <div class="flex-right center">
                <x-button action="submit" icon="upload" label="Wgraj" />
            </div>
        </form>
    </x-magazyn-section>

    <x-magazyn-section title="Zarządzanie folderem" class="flex-down">
        <form action="{{ route('folder-create') }}" method="POST" class="flex-down">
            @csrf
            <input type="hidden" name="path" value="{{ request("path") }}">

            <x-input-field name="name" label="Utwórz nowy podfolder, podając jego nazwę" />
            <span class="ghost">Utworzony zostanie nowy folder w katalogu <strong>{{ request("path", "głównym") }}</strong></span>

            <div class="flex-right center">
                <x-button action="submit" icon="folder-plus" label="Utwórz" />
            </div>
        </form>

        <div class="flex-right center">
            <x-button :action="route('folder-delete', ['path' => request('path')])"
                icon="folder-remove"
                class="danger phantom"
                label="Usuń obecny folder i jego zawartość"
            />
        </div>
    </x-magazyn-section>
</div>

@endsection
