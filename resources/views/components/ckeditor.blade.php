@props([
    "label",
    "name",
    "value" => null,
])

<label for="{{ $name }}">{{ $label }}</label>
<div>
    <div class="main-container">
        <div class="editor-container editor-container_classic-editor editor-container_include-style" id="editor-container">
            <div class="editor-container__editor">
                <textarea name="{{ $name }}" id="editor">
                    {!! $value !!}
                </textarea>
            </div>
        </div>
    </div>
</div>
<script type="importmap">
{
    "imports": {
        "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
        "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
    }
}
</script>
<script type="module" src="{{ asset("js/ckeditor.js") }}?{{ time() }}"></script>
