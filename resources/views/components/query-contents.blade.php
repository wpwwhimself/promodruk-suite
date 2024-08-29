@props([
    "requestData",
    "cart",
    "files",
    "globalFiles",
])

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

<h2>Zapytanie</h2>

<table>
    <thead>
        <tr>
            <th>Produkt</th>
            <th>Atrybuty</th>
            <th>Ilość</th>
            <th>Komentarz</th>
            <th>Pliki</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($cart as $item)
        <tr>
            <td>
                <a href="{{ route('product', ['id' => $item["product"]->id]) }}">
                    {{ $item["product"]->name }} ({{ $item["product"]->id }})
                </a>
            </td>
            <td>
                @foreach ($item["attributes"] as ["attr" => $attr, "var" => $var])
                {{ $attr["name"] }}: {{ $var["name"] }}
                @endforeach
            </td>
            <td>{{ $item["amount"] }}</td>
            <td>{{ $item["comment"] }}</td>
            <td>
                @if (isset($files[$item["no"]]) && $files[$item["no"]]->count() > 0)
                <ul>
                    @foreach ($files[$item["no"]] as $file)
                    <li><a href="{{ env("APP_URL") . Storage::url($file) }}">{{ basename($file) }}</a></li>
                    @endforeach
                </ul>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<h2>Wspólne załączniki</h2>

@if (count($globalFiles) > 0)
<ul>
    @foreach ($globalFiles as $file)
    <li><a href="{{ env("APP_URL") . Storage::url($file) }}">{{ basename($file) }}</a></li>
    @endforeach
</ul>
@endif
