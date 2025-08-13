@extends("layouts.admin")
@section("title", "Konta")

@section("content")

<x-tiling>
    <x-tiling.item title="Lista kont" icon="user">
        <table>
            <thead>
                <tr>
                    <th>Nazwa</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>
                        <a href="{{ route("users-edit", $user->id) }}">Edytuj</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex-right center">
            <x-button :action="route('users-edit')" label="Utwórz nowe" icon="plus" />
        </div>
    </x-tiling.item>
</x-tiling>
@endsection
