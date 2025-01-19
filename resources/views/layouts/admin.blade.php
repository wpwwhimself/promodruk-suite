<!DOCTYPE html>
<html lang="en">
<x-layout.head />
<body>
    <div id="main-wrapper" class="flex-down">
        <h1>
            @yield("title")
            <small class="ghost">Panel administratora</small>
        </h1>

        @auth
        <x-top-nav :pages="\App\Http\Controllers\AdminController::$pages" />
        @endauth

        <main class="flex-down">
            @yield("interactives")

            <div id="content">
                @yield("content")
            </div>

            @yield("interactives")
        </main>
    </div>
    <x-footer />

    @foreach (["success", "error"] as $status)
    @if (session($status))
    <x-popup-alert :status="$status" />
    @endif
    @endforeach

    @bukScripts(true)
</body>
</html>
