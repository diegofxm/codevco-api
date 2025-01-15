<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura {{ $invoice->prefix }}-{{ $invoice->number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .company-info {
            float: left;
            width: 60%;
        }
        .invoice-info {
            float: right;
            width: 35%;
            text-align: right;
        }
        .clear {
            clear: both;
        }
        .customer-info {
            margin: 20px 0;
            padding: 10px;
            background: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #333;
            color: white;
            padding: 8px;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .totals {
            float: right;
            width: 35%;
            margin-top: 20px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 5px;
        }
        .totals tr:last-child {
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .qr-code img {
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h2>{{ $invoice->company->business_name }}</h2>
            <p>
                NIT: {{ $invoice->company->document_number }}<br>
                {{ $invoice->company->address }}<br>
                {{ $invoice->company->phone }}<br>
                {{ $invoice->company->email }}
            </p>
        </div>
        <div class="invoice-info">
            <h1>FACTURA ELECTRÓNICA</h1>
            <h3>{{ $invoice->prefix }}-{{ $invoice->number }}</h3>
            <p>
                Fecha: {{ $invoice->issue_date->format('Y-m-d') }}<br>
                Hora: {{ $invoice->issue_date->format('H:i:s') }}<br>
                Vence: {{ $invoice->payment_due_date->format('Y-m-d') }}
            </p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="customer-info">
        <h3>DATOS DEL CLIENTE</h3>
        <p>
            <strong>{{ $invoice->customer->business_name }}</strong><br>
            NIT/CC: {{ $invoice->customer->document_number }}<br>
            Dirección: {{ $invoice->customer->address }}<br>
            Teléfono: {{ $invoice->customer->phone }}<br>
            Email: {{ $invoice->customer->email }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Precio Unit.</th>
                <th>Dcto.</th>
                <th>IVA</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
            <tr>
                <td>{{ $line->product->code }}</td>
                <td>{{ $line->description }}</td>
                <td>{{ number_format($line->quantity, 2) }}</td>
                <td>${{ number_format($line->price, 2) }}</td>
                <td>{{ number_format($line->discount_rate, 2) }}%</td>
                <td>{{ number_format($line->tax->rate, 2) }}%</td>
                <td>${{ number_format($line->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td>${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Descuento:</td>
                <td>${{ number_format($invoice->total_discount, 2) }}</td>
            </tr>
            <tr>
                <td>IVA:</td>
                <td>${{ number_format($invoice->total_tax, 2) }}</td>
            </tr>
            <tr>
                <td>Total:</td>
                <td>${{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>

    <div class="qr-code">
        <img src="{{ $qrPath }}" alt="QR Code">
        <p>CUFE: {{ $invoice->cufe }}</p>
    </div>

    <div class="footer">
        <p>Esta factura electrónica cumple con los requisitos de la DIAN según la resolución {{ $invoice->resolution->number }}</p>
        <p>{{ $invoice->notes }}</p>
    </div>
</body>
</html>
