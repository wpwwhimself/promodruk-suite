@extends("layouts.app")
@section("title", implode(" | ", [$user->name ?? "Nowe konto", "Edycja konta"]))

@section("content")

<form action="{{ route("users.process") }}" method="POST" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $user?->id }}">

    <div class="grid" style="--col-count: 2;">
        <x-app.section title="Dane konta" class="flex-down">
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

            @if (userIs("technical") && $user)
            <div class="flex-right">
                <a href="{{ route('users.reset-password', ['user_id' => $user?->id]) }}"
                    class="button danger"
                >
                    Resetuj hasło
                </a>
            </div>
            @endif
        </x-app.section>

        @if (userIs("technical"))
        <x-app.section title="Role" class="flex-down">
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

        <x-app.section title="Domyślne rabaty" class="flex-down">
            <p class="ghost">Określ domyślne rabaty, jakie będą się pojawiać podczas tworzenia oferty.</p>

            <div class="table" style="--col-count: 3;">
                <span class="head">Dostawca</span>
                <span class="head">Rabat prod.</span>
                <span class="head">Rabat znak.</span>

                <hr>

                @foreach ($suppliers as $supplier)
                <span>{{ $supplier->name }}</span>
                @foreach ($discount_types as $type)
                <span>
                    <x-input-field type="number"
                        name="default_discounts[{{ $supplier->name }}][{{ $type }}]"
                        :value="$user?->default_discounts[$supplier->name][$type] ?? 0"
                        :disabled="!in_array($type, $supplier->allowed_discounts ?? [])"
                    />
                </span>
                @endforeach
                @endforeach
            </div>
        </x-app.section>
    </div>


    <div class="section flex-right center middle">
        <button type="submit">Zapisz</button>
    </div>
</form>

@endsection
