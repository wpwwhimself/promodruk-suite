@extends("layouts.admin")
@section("title", "Kokpit")

@section("content")

<h2>Ustawienia ogólne</h2>

<form action="{{ route('update-settings') }}" method="POST">
    @csrf

    @foreach ($available_settings as $setting)
    <x-input-field type="text"
        :name="$setting->name"
        :label="$setting->label"
        :value="$setting->value"
    />
    @endforeach

    <button type="submit">Zapisz</button>
</form>

@endsection
