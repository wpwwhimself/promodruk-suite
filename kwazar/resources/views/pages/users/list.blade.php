@extends("layouts.app")
@section("title", "Konta")

@section("content")

<x-app.section title="Lista kont">
    <x-slot:buttons>
        <a class="button" href="{{ route("users.edit") }}">Utwórz nowe</a>
    </x-slot:buttons>

    <table>
        <thead>
            <tr>
                <th>Nazwa</th>
                <th>Login</th>
                <th>Email</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->login }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <a href="{{ route("users.edit", $user->id) }}">Edytuj</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</x-app.section>
@endsection
