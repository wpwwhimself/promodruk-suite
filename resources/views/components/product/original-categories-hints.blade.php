<div class="hints flex-right wrap">
    @foreach ($hints as $hint)
    <span class="button"
        onclick="
            document.getElementById('{{ $input_id }}').value = '{{ $hint }}'
            document.querySelector('[for={{ $input_id }}] .hints').innerHTML = ''
        "
    >
        {{ $hint }}
    </span>
    @endforeach
</div>
