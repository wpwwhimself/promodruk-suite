<!DOCTYPE html>
<html lang="en">
<x-layout.head />
<body class="flex-down center">
    <div id="main-wrapper" class="flex-down">
        <h1>
            @yield("title")
            <small class="ghost">Panel administratora</small>
        </h1>

        <x-top-nav :pages="\App\Http\Controllers\AdminController::$pages" />

        <main class="flex-down">
        @yield("content")
        </main>
    </div>
    <x-footer />

    @foreach (["success", "error"] as $status)
    @if (session($status))
    <x-popup-alert :status="$status" />
    @endif
    @endforeach
</body>
</html>
