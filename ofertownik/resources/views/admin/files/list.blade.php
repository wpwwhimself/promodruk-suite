@extends("layouts.admin")

@section("title", "Repozytorium plików")

@section("content")

<p class="ghost">
    Tutaj możesz umieszczać pliki – np. grafiki – które mają pojawić się na podstronach.
    Po wgraniu ich na serwer możesz je umieścić w treściach strony, korzystając z wygenerowanych linków.
</p>

<x-button icon="search" :action="route('files-search')" label="Znajdź pliki" />

<div>
    <div class="flex-right middle">
        <div class="ghost">Jesteś tutaj:</div>

        <h3 style="margin: 0;">{{ request('path', 'Katalog główny') }}</h3>

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

    <x-tiling count="auto">
        @forelse ($files as $file)
        <x-tiling.item :title="Str::afterLast($file, '/')"
            :img="isPicture($file) ? Storage::url($file).'?'.time() : null"
        >
            <x-slot:buttons>
                @if (request()->has("select"))
                <x-button icon="check" label="Wybierz" class="interactive" onclick="selectFile('{{ asset(Storage::url($file)) }}', '{{ request('select') }}')" />
                @else
                <x-button :action="route('files-download', ['file' => $file])" target="_blank" icon="download" label="Pobierz" class="phantom" />
                <x-button action="none" icon="link" onclick="copyToClipboard('{{ asset(Storage::url($file)) }}')" label="Link" />
                <x-button action="none" icon="swap-horizontal" onclick="initFileReplace('{{ Str::afterLast($file, '/') }}')" label="Podmień" />
                <x-button :action="route('files-delete', ['file' => $file])" icon="delete" label="Usuń" class="danger" />
                @endif
            </x-slot:buttons>
        </x-tiling.item>
        @empty
        <p class="ghost">Brak plików</p>
        @endforelse
    </x-tiling>
</div>

<x-tiling count="2" class="stretch-tiles">
    <x-tiling.item title="Wgrywanie plików">
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
    </x-tiling.item>

    <x-tiling.item title="Zarządzanie folderem" class="flex-down">
        <form action="{{ route('folder-create') }}" method="POST" class="flex-down">
            @csrf
            <input type="hidden" name="path" value="{{ request("path") }}">

            <x-input-field name="name" label="Utwórz nowy podfolder, podając jego nazwę" />
            <span class="ghost">Utworzony zostanie nowy folder w katalogu <strong>{{ request("path", "głównym") }}</strong></span>

            <div class="flex-right center">
                <x-button action="submit" icon="folder-add" label="Utwórz" />
            </div>
        </form>

        <div class="flex-right center">
            <x-button :action="route('folder-delete', ['path' => request('path')])"
                icon="folder-remove"
                class="danger phantom"
                label="Usuń obecny folder i jego zawartość"
            />
        </div>
    </x-tiling.item>
</x-tiling>

@endsection
