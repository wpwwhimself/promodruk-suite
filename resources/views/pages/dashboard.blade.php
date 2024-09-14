@extends("layouts.app")
@section("title", "Kokpit")

@section("content")

<x-app-section title="O użytkowniku">
    <p>Zalogowany jako <strong>{{ Auth::user()->name }}</strong></p>
    <p>Nadane role:</p>
    <ul>
        @forelse (Auth::user()->roles as $role)
        <li>
            {{ $role->name }}
            <strong class="success" {{ Popper::pop($role->description) }}>(?)</strong>
        </li>
        @empty
        <li class="ghost">Brak nadanych ról</li>
        @endforelse
    </ul>
</x-app-section>

@endsection
