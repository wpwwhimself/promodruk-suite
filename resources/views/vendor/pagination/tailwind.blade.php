<nav role="pagination" aria-label="{{ __('Pagination Navigation') }}" class="flex-right">
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

    @if ($paginator->hasPages())
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
    @endif

    <x-multi-input-field :options="['25' => 25, '50' => 50, '100' => 100, '200' => 200,]"
        label="Pozycji na stronie" name="perPage"
        :value="$paginator->perPage()"
        onchange="changePerPage(event.target.value)"
    />

    <script>
    const changePerPage = (per_page) => {
        window.location.href = `{!! $paginator->url(1) !!}&perPage=${per_page}`
    }
    </script>

    <x-multi-input-field
        :options="$availableSorts"
        label="Sortuj" name="sortBy"
        :value="request('sortBy', 'price')"
        onchange="changeSortBy(event.target.value)"
    />

    <script>
    const changeSortBy = (sort_by) => {
        window.location.href = `{!! $paginator->url(1) !!}&sortBy=${sort_by}`
    }
    </script>

    @if (isset($availableFilters))
        @foreach ($availableFilters as [$name, $label, $options])
        {{-- @if ($label == "Kolor")
        <div class="input-container">
            <label for="filter">{{ $label }}</label>
            <div class="flex-right wrap">
                @forelse ($options as $color)
                <x-color-tag :color="collect($color)"
                    :link="collect(request('filters'))->get('color') == $color['name']
                        ? preg_replace('/&?filters\[color\]=[a-zA-ZąćęłóśźżĄĆĘŁÓŚŹŻ]+/', '', urldecode($paginator->url(1)))
                        : $paginator->url(1).'&filters[color]='.$color['name']
                    "
                    :active="collect(request('filters'))->get('color') == $color['name']"
                />
                @empty
                <p class="ghost">Brak kolorów</p>
                @endforelse
            </div>
        </div>
        @else --}}
        <x-multi-input-field
            :options="$options"
            :label="$label"
            :name="$name"
            :value="collect(request('filters'))->get($name)"
            onchange="changeFilterBy(event.target.name, event.target.value)"
            :empty-option="$name == 'availability' ? false : 'dowolny'"
        />
        {{-- @endif --}}
        @endforeach

        <script>
        const changeFilterBy = (name, value) => {
            const re = new RegExp(`&?filters\\[${name}\\]=[a-zA-ZąćęłóśźżĄĆĘŁÓŚŹŻ]+`, "gi")
            window.location.href = (!value)
                ? `{!! urldecode($paginator->url(1)) !!}`.replace(new RegExp(`&?filters\\[${name}\\]=([a-zA-ZąćęłóśźżĄĆĘŁÓŚŹŻ]|\\s)+`, "gi"), '')
                : `{!! $paginator->url(1) !!}&filters[${name}]=${value}`
        }
        </script>
    @endif
</nav>
