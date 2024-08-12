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
        <td>
            {{ $request_data["street_name"] }}
            {{ $request_data["street_number"] }},
            {{ $request_data["zip_code"] }}
            {{ $request_data["city"] }}
        </td>
    </tr>
    <tr>
        <td>Adres e-mail</td>
        <td>{{ $request_data["email_address"] }}</td>
    </tr>
    <tr>
        <td>Numer telefonu</td>
        <td>{{ $request_data["phone_number"] }}</td>
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
        </tr>
        @endforeach
    </tbody>
</table>

@if (count($files))
<h2>Załączniki</h2>

<ul>
    @foreach ($files as $file)
    <li><a href="{{ storage_path($file) }}">{{ $file }}</a></li>
    @endforeach
</ul>
@endif

@endsection
