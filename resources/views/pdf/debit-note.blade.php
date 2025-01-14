<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Débito</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 200px;
            max-height: 100px;
        }
        .company-info {
            text-align: right;
        }
        .info-header {
            background-color: #666;
            color: white;
            padding: 5px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        .totals {
            float: right;
            width: 300px;
        }
        .qr-container {
            float: left;
            width: 150px;
            text-align: center;
        }
        .footer {
            clear: both;
            margin-top: 30px;
            font-size: 10px;
        }
        .notes {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td width="50%">
                <img src="{{ $debitNote->company->logo_url }}" class="logo">
            </td>
            <td width="50%" class="company-info">
                <strong>{{ $debitNote->company->business_name }}</strong><br>
                NIT: {{ $debitNote->company->document_number }}<br>
                {{ $debitNote->company->address }}<br>
                {{ $debitNote->company->location->city }}, {{ $debitNote->company->location->department->name }}<br>
                Tel: {{ $debitNote->company->phone }}<br>
                Email: {{ $debitNote->company->email }}
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td width="50%">
                <div class="info-header">Cliente</div>
                <strong>{{ $debitNote->invoice->customer->business_name }}</strong><br>
                NIT: {{ $debitNote->invoice->customer->document_number }}<br>
                {{ $debitNote->invoice->customer->address }}<br>
                {{ $debitNote->invoice->customer->location->city }}, {{ $debitNote->invoice->customer->location->department->name }}<br>
                Tel: {{ $debitNote->invoice->customer->phone }}<br>
                Email: {{ $debitNote->invoice->customer->email }}
            </td>
            <td width="50%">
                <div class="info-header">Nota Débito Electrónica</div>
                Número: {{ $debitNote->prefix }}-{{ $debitNote->number }}<br>
                Fecha de Expedición: {{ optional($debitNote->issue_date)->format('Y-m-d') ?? 'N/A' }}<br>
                Factura Relacionada: {{ $debitNote->invoice->prefix }}-{{ $debitNote->invoice->number }}
            </td>
        </tr>
    </table>

    <div class="info-header">Concepto de Corrección</div>
    <p>{{ $debitNote->correction_concept }}</p>

    <table>
        <thead>
            <tr>
                <th>Cantidad</th>
                <th>Descripción</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debitNote->lines as $item)
            <tr>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->description }}</td>
                <td>$ {{ number_format($item->unit_price, 2) }}</td>
                <td>$ {{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td>$ {{ number_format($debitNote->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Descuento:</td>
                <td>$ {{ number_format($debitNote->total_discount, 2) }}</td>
            </tr>
            <tr>
                <td>IVA:</td>
                <td>$ {{ number_format($debitNote->total_tax, 2) }}</td>
            </tr>
            <tr>
                <th>Total:</th>
                <th>$ {{ number_format($debitNote->total_amount, 2) }}</th>
            </tr>
        </table>
    </div>

    <div class="qr-container">
        {!! QrCode::size(150)->generate($debitNote->cufe) !!}
    </div>

    <div class="footer">
        <div class="notes">
            {{ $debitNote->notes }}<br><br>
            <strong>CUFE:</strong><br>
            {{ $debitNote->cufe }}<br><br>
            <strong>Resolución de Facturación No:</strong> {{ $debitNote->resolution->number }} del {{ optional($debitNote->resolution->date)->format('Y-m-d') ?? 'N/A' }}<br>
            Rangos de numeración desde: {{ $debitNote->resolution->from }}, hasta: {{ $debitNote->resolution->to }}<br>
            Vigencia: desde {{ optional($debitNote->resolution->start_date)->format('Y-m-d') ?? 'N/A' }}, hasta: {{ optional($debitNote->resolution->end_date)->format('Y-m-d') ?? 'N/A' }}
        </div>
    </div>
</body>
</html>
