@extends("layouts.admin")
@section("title", implode(" | ", [$user->name ?? "Nowe konto", "Edycja konta"]))

@section("content")

<form action="{{ route("update-users") }}" method="POST" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $user?->id }}">

    <div class="grid" style="--col-count: 2;">
        <x-magazyn-section title="Dane konta" class="flex-down">
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

            @if (Auth::user()->hasRole("technical") && $user)
            <div class="flex-right">
                <a href="{{ route('users.reset-password', ['user_id' => $user?->id]) }}"
                    class="button danger"
                >
                    Resetuj hasło
                </a>
            </div>
            @endif
        </x-magazyn-section>

        @if (Auth::user()->hasRole("technical"))
        <x-magazyn-section title="Role" class="flex-down">
            @foreach ($roles as $role)
            <div class="input-container">
                <input type="checkbox"
                    id="roles_{{ $role->name }}"
                    name="roles[]"
                    value="{{ $role->id }}"
                    @if ($user?->roles->contains($roles->firstWhere('id', $role->id))) checked @endif
                />
                <label for="roles_{{ $role->name }}">{{ $role->name }}: {{ $role->description }}</label>
            </div>
            @endforeach
        </x-magazyn-section>
        @endif
    </div>


    <div class="section flex-right center middle">
        <button type="submit">Zapisz</button>
    </div>
</form>

@endsection
