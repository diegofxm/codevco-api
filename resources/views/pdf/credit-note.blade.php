<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Crédito</title>
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
                <img src="{{ $creditNote->company->logo_url }}" class="logo">
            </td>
            <td width="50%" class="company-info">
                <strong>{{ $creditNote->company->business_name }}</strong><br>
                NIT: {{ $creditNote->company->document_number }}<br>
                {{ $creditNote->company->address }}<br>
                {{ $creditNote->company->location->city }}, {{ $creditNote->company->location->department->name }}<br>
                Tel: {{ $creditNote->company->phone }}<br>
                Email: {{ $creditNote->company->email }}
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td width="50%">
                <div class="info-header">Cliente</div>
                <strong>{{ $creditNote->invoice->customer->business_name }}</strong><br>
                NIT: {{ $creditNote->invoice->customer->document_number }}<br>
                {{ $creditNote->invoice->customer->address }}<br>
                {{ $creditNote->invoice->customer->location->city }}, {{ $creditNote->invoice->customer->location->department->name }}<br>
                Tel: {{ $creditNote->invoice->customer->phone }}<br>
                Email: {{ $creditNote->invoice->customer->email }}
            </td>
            <td width="50%">
                <div class="info-header">Nota Crédito Electrónica</div>
                Número: {{ $creditNote->prefix }}-{{ $creditNote->number }}<br>
                Fecha de Expedición: {{ optional($creditNote->issue_date)->format('Y-m-d') ?? 'N/A' }}<br>
                Factura Relacionada: {{ $creditNote->invoice->prefix }}-{{ $creditNote->invoice->number }}
            </td>
        </tr>
    </table>

    <div class="info-header">Concepto de Corrección</div>
    <p>{{ $creditNote->correction_concept }}</p>

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
            @foreach($creditNote->lines as $item)
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
                <td>$ {{ number_format($creditNote->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Descuento:</td>
                <td>$ {{ number_format($creditNote->total_discount, 2) }}</td>
            </tr>
            <tr>
                <td>IVA:</td>
                <td>$ {{ number_format($creditNote->total_tax, 2) }}</td>
            </tr>
            <tr>
                <th>Total:</th>
                <th>$ {{ number_format($creditNote->total_amount, 2) }}</th>
            </tr>
        </table>
    </div>

    <div class="qr-container">
        {!! QrCode::size(150)->generate($creditNote->cufe) !!}
    </div>

    <div class="footer">
        <div class="notes">
            {{ $creditNote->notes }}<br><br>
            <strong>CUFE:</strong><br>
            {{ $creditNote->cufe }}<br><br>
            <strong>Resolución de Facturación No:</strong> {{ $creditNote->resolution->number }} del {{ optional($creditNote->resolution->date)->format('Y-m-d') ?? 'N/A' }}<br>
            Rangos de numeración desde: {{ $creditNote->resolution->from }}, hasta: {{ $creditNote->resolution->to }}<br>
            Vigencia: desde {{ optional($creditNote->resolution->start_date)->format('Y-m-d') ?? 'N/A' }}, hasta: {{ optional($creditNote->resolution->end_date)->format('Y-m-d') ?? 'N/A' }}
        </div>
    </div>
</body>
</html>
