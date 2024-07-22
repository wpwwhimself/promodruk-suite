<aside>
    <h2>Kategorie</h2>

    <ul>
    @foreach ($categories as $category)
        <a class="animatable"
        @if ($category->external_link)
            href="{{ $category->external_link }}" _target="blank"
        @else
            href=""
        @endif
        >
            {{ $category->name }}
        </a>
    @endforeach
    </ul>
</aside>
