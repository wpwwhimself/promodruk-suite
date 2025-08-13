@extends("layouts.admin")
@section("title", implode(" | ", [$user->name ?? "Nowe konto", "Edycja konta"]))

@section("content")

<form action="{{ route("update-users") }}" method="POST" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $user?->id }}">

    <x-tiling>
        <x-tiling.item title="Dane konta" class="flex-down">
            <x-input-field type="text"
                name="name"
                label="Nazwa"
                :value="$user?->name"
                required
                autofocus
            />

            @unless ($user)
            <p class="ghost">
                Nowo utworzony użytkownik otrzyma hasło takie samo jak jego login.
                Przy logowaniu będzie poproszony o jego zmianę.
            </p>
            @endunless

            @if (userIs("Administrator") && $user)
            <div class="flex-right">
                <x-button :action="route('users.reset-password', ['user_id' => $user?->id])"
                    class="button danger"
                    label="Resetuj hasło"
                    icon="lock-open"
                />
            </div>
            @endif
        </x-tiling.item>

        @if (userIs("Administrator"))
        <x-tiling.item title="Role" class="flex-down">
            @foreach ($roles as $role)
            <div class="input-container">
                <input type="checkbox"
                    id="roles_{{ $role->name }}"
                    name="roles[]"
                    value="{{ $role->name }}"
                    @if ($user?->roles->contains($roles->firstWhere('name', $role->name))) checked @endif
                />
                <label for="roles_{{ $role->name }}">{{ $role->name }}: {{ $role->description }}</label>
            </div>
            @endforeach
        </x-tiling.item>
        @endif
    </x-tiling>


    <div class="section flex-right center middle">
        <button type="submit" name="mode" value="save">Zapisz</button>

        @if ($user && userIs("Administrator") && $user->name != "super")
        <button type="submit" name="mode" value="delete"
            class="danger"
        >
            Usuń
        </button>
        @endif
    </div>
</form>

@endsection
