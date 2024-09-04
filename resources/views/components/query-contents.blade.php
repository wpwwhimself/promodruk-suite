@props([
    "requestData" => null,
    "cart" => null,
    "files" => null,
    "globalFiles" => null,
])

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

@if ($cart)
<h2>Zapytanie</h2>

<table>
    <tbody>
        @foreach ($cart as $item)
        <tr>
            <td>
                <img src="{{ $item["product"]->images->first() }}" class="thumbnail">
            </td>
            <td>
                <div>
                    <h3><a href="{{ route('product', ['id' => $item["product"]->id]) }}">{{ $item["product"]->name }} ({{ $item["product"]->id }})</a></h3>

                    @foreach ($item["attributes"] as ["attr" => $attr, "var" => $var])
                    <span>{{ $attr["name"] }}: {{ $var["name"] }}</span>
                    @endforeach

                    <span>Ilość: {{ $item["amount"] }}</span>
                    <span>Komentarz: {{ $item["comment"] }}</span>

                    @if (isset($files[$item["no"]]) && $files[$item["no"]]->count() > 0)
                    <span>Załączniki:</span>
                    <ul>
                        @foreach ($files[$item["no"]] as $file)
                        <li><a href="{{ env("APP_URL") . Storage::url($file) }}">{{ basename($file) }}</a></li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if (count($globalFiles) > 0)
<h2>Wspólne załączniki</h2>
<ul>
    @foreach ($globalFiles as $file)
    <li><a href="{{ env("APP_URL") . Storage::url($file) }}">{{ basename($file) }}</a></li>
    @endforeach
</ul>
@endif
