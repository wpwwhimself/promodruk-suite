@extends("layouts.admin")
@section("title", "Kokpit")

@section("content")

<h2>O użytkowniku</h2>
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

@if (userIs("Administrator"))
<a href="https://github.com/wpwwhimself/promodruk-magazyn/tree/main/docs">Dokumentacja</a>
@endif

@endsection
