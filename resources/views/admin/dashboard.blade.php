@extends("layouts.admin")
@section("title", "Kokpit")

@section("content")

<x-tiling count="1" class="stretch-tiles">
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
                <tr>
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

    </x-tiling.item>

    <x-tiling.item title="Film pokazowy" icon="horn">
        <form action="{{ route('update-settings') }}" method="post">
            @csrf

            @foreach ($showcase_settings as $s)
            @if ($s->name == "showcase_visible")
            <x-multi-input-field
                :name="$s->name"
                :label="$s->label"
                :value="$s->value"
                :options="[
                    'Ukryty' => 0,
                    'Prywatny' => 1,
                    'Publiczny' => 2,
                ]"
            />
            @else
            <x-input-field
                type="text"
                :name="$s->name"
                :label="$s->label"
                :value="$s->value"
            />
            @endif
            @endforeach

            <div class="flex-right center">
                <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
            </div>
        </form>
    </x-tiling.item>
</x-tiling>

@endsection
