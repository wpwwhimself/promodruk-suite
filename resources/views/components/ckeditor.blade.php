@props([
    "label" => null,
    "name",
    "value" => null,
])

<div class="input-container">
    @if ($label)
    <label for="{{ $name }}">{{ $label }}</label>
    @endif

    <div>
        <div class="main-container">
            <div class="editor-container editor-container_classic-editor editor-container_include-style" id="editor-container">
                <div class="editor-container__editor">
                    <textarea name="{{ $name }}" class="ckeditor">
                        {!! $value !!}
                    </textarea>
                </div>
            </div>
        </div>
    </div>
</div>

