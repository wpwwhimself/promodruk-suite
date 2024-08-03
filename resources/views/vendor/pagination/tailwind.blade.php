@if ($paginator->hasPages())
    <nav role="pagination" aria-label="{{ __('Pagination Navigation') }}">
        <div>
            <p>
                Wyświetlanie
                @if ($paginator->firstItem())
                    <span>{{ $paginator->firstItem() }}</span>
                    -
                    <span>{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                z
                <span>{{ $paginator->total() }}</span>
                wyników
            </p>
        </div>

        <div class="flex-right center">
            {{-- Previous Page Link --}}
            <x-button :action="$paginator->onFirstPage() ? null : $paginator->previousPageUrl()" label="Poprzednia" hide-label icon="arrow-left" />

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true">
                        <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5 dark:bg-gray-800 dark:border-gray-600">{{ $element }}</span>
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <x-button :action="$paginator->currentPage() == $page ? null : $url" :label="$page" :class="$paginator->currentPage() == $page ? 'active' : null" />
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            <x-button :action="$paginator->hasMorePages() ? $paginator->nextPageUrl() : null" label="Następna" hide-label icon="arrow-right" />
        </div>
</nav>
@endif
