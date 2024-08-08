<aside>
    <h2>Kategorie produktów</h2>

    <ul>
    @foreach ($categories as $category)
        <x-sidebar.category :category="$category" />
    @endforeach
    </ul>
</aside>
