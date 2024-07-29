@props([
    "images",
])

<div class="photo-gallery">

<img src="{{ $images[0] }}" id="main-photo">
<div class="list flex-right wrap">
    @foreach ($images as $img)
    <img onclick="highlightPhoto(this)" src="{{ $img }}" />
    @endforeach
</div>

</div>

<script>
highlightPhoto = (img) => {
    document.getElementById("main-photo").src = img.src
}
</script>
