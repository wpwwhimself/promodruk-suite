@extends("layouts.main")
@section("title", "Produkt niedostępny")

@section("content")

<p>Produkt o kodzie {{ request("id") }} nie jest dostępny na tej stronie.</p>
<p>Za chwilę przeniesiemy Cię na stronę główną.</p>

<script>
setTimeout(() => {
    window.location.href = "{{ route("home") }}";
}, 5e3);
</script>

@endsection
