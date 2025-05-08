@props([
    "requestData" => null,
    "cart" => null,
    "files" => null,
    "globalFiles" => null,
])

@if (count($globalFiles) > 0)
<h2>Wspólne załączniki</h2>
<ul>
    @foreach ($globalFiles as $file)
    <li><a href="{{ env("APP_URL") . Storage::url($file) }}">{{ basename($file) }}</a></li>
    @endforeach
</ul>
@endif

@if ($cart)

<h2>Produkty</h2>
@foreach ($cart as $item)
<div>
    <h3><a href="{{ route('product', ['id' => $item["product"]->front_id]) }}">{{ $item["product"]->name }} ({{ $item["product"]->front_id }})</a></h3>

    <span><b>Ilość</b>: {{ $item["amount"] }}</span><br />
    <span><b>Komentarz</b>: {{ $item["comment"] }}</span><br />

    @if (isset($files[$item["no"]]) && $files[$item["no"]]->count() > 0)
    <span><b>Załączniki</b>:</span>
    <ul>
        @foreach ($files[$item["no"]] as $file)
        <li><a href="{{ env("APP_URL") . Storage::url($file) }}">{{ basename($file) }}</a></li>
        @endforeach
    </ul>
    @endif
</div>
@endforeach
@endif

@if ($requestData)
<h2>Dane kontaktowe</h2>

<table>
    <tr>
        <td>Firma</td>
        <td>{{ $requestData["company_name"] }}</td>
    </tr>
    <tr>
        <td>Adres e-mail</td>
        <td><a href="mailto:{{ $requestData["email_address"] }}">{{ $requestData["email_address"] }}</a></td>
    </tr>
    <tr>
        <td>Imię i nazwisko</td>
        <td>{{ $requestData["client_name"] }}</td>
    </tr>
    <tr>
        <td>Numer telefonu</td>
        <td><a href="tel:{{ $requestData["phone_number"] }}">{{ $requestData["phone_number"] }}</a></td>
    </tr>
    <tr>
        <td>Komentarz</td>
        <td>{{ $requestData["final_comment"] }}</td>
    </tr>
</table>
@endif
