@extends("layouts.admin")
@section("title", "Kokpit")

@section("content")

<h2>Ustawienia ogólne</h2>

<form action="{{ route('update-settings') }}" method="POST">
    @csrf

    @foreach ($general_settings as $setting)
    <x-input-field :type="(strpos($setting->name, 'color') !== false) ? 'color' : 'text'"
        :name="$setting->name"
        :label="$setting->label"
        :value="$setting->value"
    />
    @endforeach

    <button type="submit">Zapisz</button>
</form>

<h2>Logo strony</h2>

<form action="{{ route('update-logo') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <x-logo />
    <x-input-field type="file" name="logo" label="Logo" />
    <div class="ghost">Plik logo powinien mieć rozszerzenie <code>.png</code></div>

    <button type="submit">Zapisz</button>
</form>

<h2>Tekst powitalny</h2>

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

    <button type="submit">Zapisz</button>
</form>

@endsection
