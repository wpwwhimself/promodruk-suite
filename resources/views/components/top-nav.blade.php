<nav id="top-nav" class="flex-right">
    @foreach ($pages as $page)
    <a href="{{ route($page->slug) }}" class="padded">
        {{ $page->name }}
    </a>
    @endforeach
</nav>
