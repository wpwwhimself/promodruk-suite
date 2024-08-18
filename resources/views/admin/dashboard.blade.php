@extends("layouts.admin")
@section("title", "Kokpit")

@section("content")

<x-tiling class="stretch-tiles">
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

            <x-input-field type="TEXT"
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
        <form action="{{ route('update-settings') }}" method="post">
            @csrf

            @foreach ($queries_settings as $setting)
            <x-input-field type="email"
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
</x-tiling>

@endsection
