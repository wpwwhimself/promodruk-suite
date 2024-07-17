<!DOCTYPE html>
<html lang="en">
<x-layout.head />
<body>
    <h1>Panel administratora</h1>

    <x-top-nav :pages='[
        ["Ogólne", "dashboard"]
    ]' />

    <main class="flex-down center-both">
    @yield("content")
    </main>
    <x-footer />

    @foreach (["success", "error"] as $status)
    @if (session($status))
    <x-popup-alert :status="$status" />
    @endif
    @endforeach
</body>
</html>
