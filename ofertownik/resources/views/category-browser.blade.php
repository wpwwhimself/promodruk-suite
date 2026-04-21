@extends("layouts.main")
@section("title", "Kategorie główne")

@section("before-main")
@if (userCanSeeWithSetting("showcase_visible"))
<x-showcase />
@endif
@endsection

@section("content")

<script>
function getCategory(category_id = "") {
    fetchComponent(
        `#category-browser [role="loader"]`,
        `/api/front/category/${category_id}`,
        {
            headers: {
                whoami: "{{ Auth::id() }}",
            },
        },
        [
            [`#category-browser .contents`, `tiles`],
            [`[role="sidebar-categories"] [role="list"]`, `sidebar`],
        ],
        (res) => {
            document.querySelector(`[role="sidebar-categories"] [role="loader"]`)?.classList.add("hidden");

            document.querySelector(`#showcase`).classList.toggle("hidden", category_id != "");
            window.scrollTo({top: 0, behavior: "smooth"});
            document.title = [res.data?.name ?? "Kategorie główne", "{{ setting('app_name') }}"].join(" | ");
            window.history.pushState({tiles: res.tiles, sidebar: res.sidebar}, null, res.data ? `/kategorie/${res.data.slug}` : "/");

            reapplyPopper();
        },
        {
            customError: `<p class="danger">Nie udało się pobrać kategorii. Pracujemy nad naprawieniem problemu. Spróbuj ponownie później.</p>`,
        },
    );
}
</script>

<div id="category-browser">
    <x-loader />
    <div class="contents"></div>
</div>

<script defer>
// init
getCategory({{ $category?->id }});
// ensure navigation works normally
window.addEventListener("popstate", event => {
    const {tiles, sidebar} = event.state;
    if (tiles && sidebar) {
        document.querySelector(`#category-browser .contents`).innerHTML = tiles;
        document.querySelector(`[role='sidebar-categories'] [role="list"]`).innerHTML = sidebar;
    }
});
</script>

{{-- ♻️ Ukrycie tytułu strony ♻️ --}}
<script>
document.querySelector("main > div:has(h1)").remove();
</script>
{{-- ♻️ Ukrycie tytułu strony ♻️ --}}

@endsection
