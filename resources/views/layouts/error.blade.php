@extends("layouts.base")

@section("insides")

<x-sidebar />
<main style="padding-inline: 0; text-align: center;">
    @yield("before-title")

    <h1 style="font-size: 3em;">
        {{ $exception->getStatusCode() }} | @yield("title")
    </h1>

    <p>
        @yield("description")
    </p>

    <p class="ghost">
        {{ $exception->getMessage() }}
    </p>

    @yield("interactives")
</main>

@endsection
