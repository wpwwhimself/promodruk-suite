@extends("layouts.admin")
@section("title", "Ustawienia")

@section("content")

<x-tiling count="auto" class="stretch-tiles">
    <x-tiling.item title="Ustawienia ogólne" icon="settings">
        <form action="{{ route('update-settings') }}" method="POST">
            @csrf

            @foreach ($general_settings as $setting)
            <x-input-field :type="(strpos($setting->name, 'color') !== false) ? 'color' : 'text'"
                :name="$setting->name"
                :label="$setting->label"
                :value="$setting->value"
            />
            @endforeach

            <div class="flex-right center">
                <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
            </div>
        </form>
    </x-tiling.item>

    <x-tiling.item title="Logo strony" icon="sun">
        <form action="{{ route('update-logo') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="flex-right center">
                <x-logo />
            </div>
            <x-input-field type="file" name="logo" label="Logo" />
            <div class="ghost">Maksymalne proporcje logo - 250:27. Szersze obrazki zostaną wizualnie zmniejszone.</div>

            <div class="flex-right center">
                <img src="{{ asset("storage/meta/favicon.png") }}?{{ time() }}" alt="favicon" class="logo">
            </div>
            <x-input-field type="file" name="favicon" label="Favicon" />
            <div class="ghost">Pliki logo i ikony strony powinny mieć rozszerzenie <code>.png</code></div>

            <div class="flex-right center">
                <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
            </div>
        </form>
    </x-tiling.item>

    <x-tiling.item title="Tekst powitalny" icon="horn">
        <form action="{{ route('update-welcome-text') }}" method="post">
            @csrf

            <x-ckeditor
                :name="$welcome_text_content->name"
                :label="$welcome_text_content->label"
                :value="$welcome_text_content->value"
            />
            <x-multi-input-field
                :name="$welcome_text_visible->name"
                :label="$welcome_text_visible->label"
                :value="$welcome_text_visible->value"
                :options="[
                    'Ukryty' => 0,
                    'Tylko strona główna' => 1,
                    'Widoczny' => 2,
                ]"
            />

            <div class="flex-right center">
                <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
            </div>
        </form>
    </x-tiling.item>

    <x-tiling.item title="Dokumentacja" icon="book">
        <x-button action="https://github.com/wpwwhimself/promodruk-ofertownik/tree/main/docs" target="_blank" label="Link" />
    </x-tiling.item>

    <x-tiling.item title="Zapytania" icon="help">
        <p>
            Poniższa lista zawiera zdefiniowanych opiekunów handlowych.
            Ci z nich oznaczeni jako widoczni pojawią się na liście wyboru dla klienta przy składaniu zapytania.
        </p>

        <table>
            <thead>
                <tr>
                    <th>Imię i nazwisko</th>
                    <th>Adres email</th>
                    <th>Widoczny</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($supervisors as $supervisor)
                <tr {{ $supervisor->visible ? "class='ghost'" : "" }}>
                    <td>{{ $supervisor->name }}</td>
                    <td>{{ $supervisor->email }}</td>
                    <td><input type="checkbox" disabled {{ $supervisor->visible ? "checked" : "" }} /></td>
                    <td>
                        <x-button :action="route('supervisor-edit', ['id' => $supervisor->id])" label="Edytuj" icon="edit" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="ghost">Brak zdefiniowanych opiekunów handlowych.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="flex-right center">
            <x-button :action="route('supervisor-edit')" label="Nowy" icon="add" />
        </div>

        <form action="{{ route('update-settings') }}" method="post">
            @csrf

            <h3>Usuwanie starych plików</h3>
            <p>
                Zdefiniuj czas, po jakim usuwane są stare pliki zapytań przechowywane na serwerze.
                Wszystkie wartości podane są w godzinach.
            </p>

            @foreach ($queries_settings as $s)
            <x-input-field type="number"
                :name="$s->name"
                :label="$s->label"
                :value="$s->value"
                step="1" min="1"
            />
            @endforeach

            <div class="flex-right center">
                <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
            </div>
        </form>
    </x-tiling.item>

    <x-tiling.item title="Konfiguracja ATF" icon="horn">
        <form action="{{ route('update-settings') }}" method="post">
            @csrf

            <p>Te ustawienia dotyczą prezentacji widocznej na stronie głównej. Może być nią film, karuzela slajdów lub prosty tekst.</p>

            @php $s = $showcase_settings->firstWhere('name', 'showcase_visible'); @endphp
            <x-multi-input-field
                :name="$s->name"
                :label="$s->label"
                :value="$s->value"
                :options="VISIBILITIES"
            />
            @php $s = $showcase_settings->firstWhere('name', 'showcase_mode'); @endphp
            <x-multi-input-field
                :name="$s->name"
                :label="$s->label"
                :value="$s->value"
                :options="\App\Models\Setting::SHOWCASE_MODES"
                onchange="document.querySelector('[role=showcase-mode-hint]').classList.remove('hidden')"
            />
            <span role="showcase-mode-hint" class="ghost hidden">Zapisz zmiany, żeby zaktualizować dostępne opcje dla tego trybu.</span>

            <p>
                @switch ($s->value)
                @case ("film")
                Pokaz automatycznie korzysta z pliku MP4 umieszczonego w katalogu <strong>meta/showcase/film</strong>.<br>
                Wyświetlany będzie pierwszy alfabetycznie plik z tego katalogu.
                @break
                @case ("carousel")
                Pokaz automatycznie korzysta z obrazków umieszczonych w katalogu <strong>meta/showcase/carousel</strong>.<br>
                Zdjęcia będą posortowane alfabetycznie.<br>
                Zalecane wymiary baneru to <strong>1016 × 200 px</strong>.<br>
                Obrazki przekraczające te proporcje zostaną przeskalowane tak, aby zawierały się w całości karuzeli.<br>
                @break
                @endswitch
            </p>

            @if (in_array($s->value, ["film", "text"]))
            @php $s = $showcase_settings->firstWhere('name', ($s->value == 'film' ? 'showcase_side_text' : 'showcase_full_width_text')); @endphp
            <x-ckeditor
                :name="$s->name"
                :label="$s->label"
                :value="$s->value"
            />
            @endif

            <div class="flex-right center">
                <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
            </div>
        </form>
    </x-tiling.item>

    <x-tiling.item title="Produkty" icon="box">
        <form action="{{ route('update-settings') }}" method="post">
            @csrf

            @foreach ($auxiliary_products_visibility_settings as $s)
            <x-multi-input-field
                :name="$s->name"
                :label="$s->label"
                :value="$s->value"
                :options="VISIBILITIES"
            />
            @endforeach

            <div class="flex-right center">
                <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
            </div>
        </form>
    </x-tiling.item>
</x-tiling>

@endsection
