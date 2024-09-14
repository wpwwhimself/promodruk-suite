@extends("layouts.base")

@section("title", implode(" | ", [
    $exception->getStatusCode(),
    $exception->getMessage(),
]))

@section("content")
<style>
main {
    text-align: center;
}
h1 {
    font-size: 3em;
}
</style>

<h1>
    @yield("title")
    <small class="ghost">@yield("subtitle")</small>
</h1>
@endsection
