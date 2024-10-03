@extends("layouts.app")
@section("title", implode(" | ", [$user->name ?? "Nowe konto", "Edycja konta"]))

@section("content")

<form action="{{ route("users.process") }}" method="POST" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $user?->id }}">

    <div class="grid" style="--col-count: 2;">
        <x-app.section title="Dane konta">
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
        </x-app.section>

        @if (userIs("technical"))
        <x-app.section title="Role">
            @foreach ($roles as $role)
            <x-input-field type="checkbox"
                name="roles[]"
                :label="$role->name . ' – ' . $role->description"
                :value="$role->name"
                :checked="$user?->roles->contains($roles->firstWhere('name', $role->name))"
            />
            @endforeach
        </x-app.section>
        @endif

        <x-app.section title="Domyślne rabaty">
            <x-input-field type="number"
                name="global_products_discount" label="Rabat na produkty (%)"
                min="0" step="0.1"
                :value="$user?->global_products_discount ?? 0"
            />
            <x-input-field type="number"
                name="global_markings_discount" label="Rabat na znakowania (%)"
                min="0" step="0.1"
                :value="$user?->global_markings_discount ?? 0"
            />
            <x-input-field type="number"
                name="global_surcharge" label="Nadwyżka (%)"
                min="0" step="0.1"
                :value="$user?->global_surcharge ?? 0"
            />
        </x-app.section>
    </div>


    <div class="section flex-right center middle">
        <button type="submit">Zapisz</button>
    </div>
</form>

@endsection
