@extends("layouts.app")
@section("title", implode(" | ", [$user->name ?? "Nowy pracownik", "Edycja pracownika"]))

@section("content")

<form action="{{ route("users.process") }}" method="POST" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $user?->id }}">

    <div class="grid" style="--col-count: 2;">
        <x-app-section title="Dane pracownika">
            <x-input-field type="text"
                name="name"
                label="Nazwa"
                :value="$user?->name"
                required
                autofocus
            />
            <x-input-field type="text"
                name="login"
                label="Login"
                :value="$user?->login"
                required
            />
            <x-input-field type="email"
                name="email"
                label="Email"
                :value="$user?->email"
            />

            @unless ($user)
            <p class="ghost">
                Nowo utworzony użytkownik otrzyma hasło takie samo jak jego login.
                Przy logowaniu będzie poproszony o jego zmianę.
            </p>
            @endunless
        </x-app-section>

        <x-app-section title="Role">
            @foreach ($roles as $role)
            <x-input-field type="checkbox"
                name="roles[]"
                :label="$role->name . ' – ' . $role->description"
                :value="$role->name"
                :checked="$user?->roles->contains($roles->firstWhere('name', $role->name))"
            />
            @endforeach
        </x-app-section>
    </div>


    <div class="section flex-right center middle">
        <button type="submit">Zapisz</button>
    </div>
</form>

@endsection
