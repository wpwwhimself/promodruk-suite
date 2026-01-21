<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield("title", "Strona główna")
            @hasSection("subtitle")
            | @yield("subtitle")
            @endif
            | {{ setting("app_name") }}
        </title>
        <link rel="icon" type="image/png" href="{{ asset(setting('app_favicon_front_path') ?? setting('app_logo_front_path')) }}">

        {{-- 💄 styles 💄 --}}
        <style>
        {!! \App\ShipyardTheme::getFontImportUrl() !!}

        :root {
            {!! \App\ShipyardTheme::getColors() !!}
            {!! \App\ShipyardTheme::getGhostColors() !!}
            {!! \App\ShipyardTheme::getFonts() !!}
        }

        :root {
            @if (setting("app_adaptive_dark_mode"))
            color-scheme: light dark;
            @else
            color-scheme: light;
            &:has(body.dark) {
                color-scheme: dark;
            }
            @endif
        }

        @if (setting("app_adaptive_dark_mode"))
        @media (prefers-color-scheme: dark) {
            .icon.invert-when-dark {
                filter: invert(1);
            }
        }
        @endif
        </style>
        <link rel="stylesheet" href="{{ asset("css/front.css") }}">

        {{-- 🚀 standard scripts 🚀 --}}
        <script src="{{ asset("js/Shipyard/earlies.js") }}?v={{ shipyard_version() }}"></script>
        {{-- 🚀 standard scripts 🚀 --}}
        <script src="{{ asset("js/front/earlies.js") }}"></script>
        <script defer src="{{ asset("js/front/app.js") }}"></script>

        {{-- ✏️ ckeditor stuff ✏️ --}}
        <script type="importmap">
        {
            "imports": {
                "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
                "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
            }
        }
        </script>
        <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css">
        <link rel="stylesheet" href="{{ asset("css/Shipyard/ckeditor.css") }}?v={{ shipyard_version() }}">
        <script type="module" src="{{ asset("js/Shipyard/ckeditor.js") }}?v={{ shipyard_version() }}"></script>
        {{-- ✏️ ckeditor stuff ✏️ --}}

        <link rel="stylesheet" href="https://unpkg.com/@glidejs/glide/dist/css/glide.core.min.css">
        <link rel="stylesheet" href="https://unpkg.com/@glidejs/glide/dist/css/glide.theme.min.css">
        <script src="https://unpkg.com/@glidejs/glide/dist/glide.js"></script>

        @include("popper::assets")
    </head>
    <body class="flex-down center">
        <script>
        // categories for listings
        /* let categories;
        (async () => await fetch("/api/categories/for-front")
            .then(res => res.json())
            .then(data => {
                categories = data;

                // init categories
                openSidebarCategory(null);
                openCategory(null);

                // open current category
                @php
                $category = \App\Models\Category::where("slug", Route::current()->parameters["slug"] ?? null)->first()
                    ?? \App\Models\Product::where("front_id", Route::current()?->id)->first()?->categories->first();
                @endphp
                @if ($category)
                let breadcrumbs = [];
                {!! $category->tree->pluck("id")->toJson() !!}.forEach((cat_id, i, arr) => {
                    // if (i == arr.length - 1) return // don't open last cat, it causes reloading loop if last cat is leaf
                    breadcrumbs.push(cat_id);
                    openSidebarCategory(breadcrumbs);
                })
                @endif

                primeSidebarCategories();
            }))(); */
        const revealInput = (name) => {
            document.querySelector(`[name="${name}"]`).classList.remove("hidden")
            document.querySelector(`[name="${name}"]`).closest(".input-container").classList.remove("hidden")
            document.querySelector(`.hidden-save`)?.classList.remove("hidden")
            document.querySelector(`.input-container[for="${name}"]`).classList.add("hidden")
        }
        const submitNearestForm = (element) => {
            element.closest("form").submit()
        }
        </script>

        <div id="header-wrapper" class="flex-down animatable">
            <x-header />
            <x-top-nav
                :pages="\App\Models\Shipyard\StandardPage::visible()
                    ->get()
                    ->map(fn ($page) => [$page->name, $page->slug])"
                with-all-products
            />
        </div>

        @yield("before-main")

        <div id="main-wrapper" class="max-width-wrapper">
            <div id="sidebar-wrapper" class="grid">
                @yield("insides")
            </div>
        </div>
        <x-footer />

        @foreach (["success", "error"] as $status)
        @if (session($status))
        <x-popup-alert :status="$status" />
        @endif
        @endforeach

        @if (session("fullscreen-popup"))
        <x-fullscreen-popup :data="session('fullscreen-popup')" />
        @endif
    </body>
</html>
