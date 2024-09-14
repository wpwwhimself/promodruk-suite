@extends("layouts.app")
@section("title", "Pracownicy")

@section("content")

<x-app-section title="Lista pracowników">
    <x-slot:buttons>
        <a class="button" href="{{ route("users.edit") }}">Utwórz nowego</a>
    </x-slot:buttons>

    <div class="table" style="--col-count: 4;">
        <span class="head">Nazwa</span>
        <span class="head">Login</span>
        <span class="head">Email</span>
        <span class="head"></span>

        <hr>

        @foreach ($users as $user)
        <span>{{ $user->name }}</span>
        <span>{{ $user->login }}</span>
        <span>{{ $user->email }}</span>
        <span>
            <a href="{{ route("users.edit", $user->id) }}">Edytuj</a>
        </span>
        @endforeach
    </div>
</x-app-section>
@endsection
