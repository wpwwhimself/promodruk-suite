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

    <p>
        Za chwilę przeniesiemy Cię na stronę główną.
    </p>

    <script>
    console.error("🚨", `{{ $exception->getMessage() }}`);

    setTimeout(() => {
        window.location.href = "{{ route("home") }}";
    }, 5e3);
    </script>

    @yield("interactives")
</main>

@endsection
