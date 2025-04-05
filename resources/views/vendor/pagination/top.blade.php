<nav role="pagination" aria-label="{{ __('Pagination Navigation') }}" class="flex-right but-mobile-down">
    @if ($paginator->hasPages())
    <div class="flex-right center">
        {{-- Previous Page Link --}}
        <x-button :action="$paginator->onFirstPage() ? null : $paginator->previousPageUrl()" label="Poprzednia" hide-label icon="arrow-left" />

        <input name="page"
            min="1" max="{{ $paginator->lastPage() }}"
            value="{{ $paginator->currentPage() }}"
            onchange="((page) => {
                if(isNaN(page)) return
                window.location.href = `{!! $paginator->url(1) !!}`.replace(/page=[0-9]+/, `page=${page}`)
            })(event.target.value)"
        >
        <span style="align-self: center">z {{ $paginator->lastPage() }} stron</span>

        {{-- Next Page Link --}}
        <x-button :action="$paginator->hasMorePages() ? $paginator->nextPageUrl() : null" label="Następna" hide-label icon="arrow-right" />
    </div>
    @endif

    {{-- <div class="flex-right center middle">
        <label>Na stronie</label>
        @foreach ([25, 50, 100] as $count)
        <x-button :action="$paginator->url(1).'&perPage='.$count" :label="$count" :class="$paginator->perPage() == $count ? 'active' : null" />
        @endforeach
    </div> --}}

    @if (isset($availableFilters) || isset($availableSorts))
        <x-button action="none"
            label="Filtry i sortowanie" icon="filter-list" icon-set="iconoir"
            class="hidden but-mobile-show" role="filters-toggle"
            onclick="
                document.querySelector(`[role=filters-toggle]`).classList.toggle(`active`)
                document.querySelectorAll(`[role=filter]`).forEach(el => el.classList.toggle(`but-mobile-hide`))
            "
        />

        @isset($availableSorts)
        <x-multi-input-field
            :options="$availableSorts"
            label="Sortuj" name="sortBy"
            :value="request('sortBy', 'price')"
            onchange="((sort_by) => {
                window.location.href = `{!! $paginator->url(1) !!}&sortBy=${sort_by}`
            })(event.target.value)"
            role="filter" class="but-mobile-hide"
        />
        @endisset

        @isset($availableFilters)
        @foreach ($availableFilters as $f)
        @php
        $name = $f[0];
        $label = $f[1];
        $options = $f[2];
        $multi = $f[3] ?? false;
        @endphp

        @if ($multi)
        <x-button action="none" :label="$label" onclick="toggleModal('filter-{{ $name }}')" />
        <x-modal id="filter-{{ $name }}">
            <h2>{{ $label }}</h2>

            @foreach ($options as $value => $label)
            <x-input-field type="checkbox" :name="$name" :value="$value" :label="$label" :checked="request('filters')?->contains($value)" />
            @endforeach

            <div class="flex-right center">
                <x-button action="submit" label="Zapisz" icon="filter" />
            </div>
        </x-modal>
        @else
        <x-multi-input-field
            :options="$options"
            :label="$label"
            :name="$name"
            :value="collect(request('filters'))->get($name)"
            onchange="((name, value) => {
                const re = new RegExp(`&?filters\\\\[${name}\\\\]=([a-zA-ZąćęłóśźżĄĆĘŁÓŚŹŻ\\\\-,]|\\\\s)+`, `gi`)
                window.location.href = (!value)
                    ? `{!! urldecode($paginator->url(1)) !!}`.replace(re, '')
                    : `{!! $paginator->url(1) !!}&filters[${name}]=${value}`
            })(event.target.name, event.target.value)"
            :empty-option="
                $name == 'availability' ? false : (
                $name == 'cat_parent_id' ? 'główne' :
                'wszystkie'
            )"
            role="filter" class="but-mobile-hide"
        />
        @endif
        @endforeach
        @endisset
    @endif

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
        </p>
    </div>
</nav>
