@extends("layouts.mail")
@section("title", "Zapytanie")

@section("content")

<h2>Dane kontaktowe</h2>

<table>
    <tr>
        <td>Firma</td>
        <td>{{ $request_data["company_name"] }}</td>
    </tr>
    <tr>
        <td>Imię i nazwisko</td>
        <td>{{ $request_data["first_name"] }} {{ $request_data["last_name"] }}</td>
    </tr>
    <tr>
        <td>Adres</td>
        @php
        $address = $request_data["street_name"] . " " . $request_data["street_number"] . ", " . $request_data["zip_code"] . " " . $request_data["city"];
        @endphp
        <td><a href="https://maps.google.com/?q={{ $address }}">{{ $address }}</a></td>
    </tr>
    <tr>
        <td>Adres e-mail</td>
        <td><a href="mailto:{{ $request_data["email_address"] }}">{{ $request_data["email_address"] }}</a></td>
    </tr>
    <tr>
        <td>Numer telefonu</td>
        <td><a href="tel:{{ $request_data["phone_number"] }}">{{ $request_data["phone_number"] }}</a></td>
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
            <td>{{ $item["product"]->name }} ({{ $item["product"]->id }})</td>
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

@endsection
