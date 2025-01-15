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
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            border: 1px solid #000;
            padding: 10px;
            vertical-align: top;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        .company-info {
            text-align: left;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            border: 1px solid #000;
            padding: 10px;
            vertical-align: top;
        }
        .info-header {
            background-color: #666;
            color: white;
            padding: 10px;
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #666;
            color: white;
            padding: 8px;
            text-align: center;
            border: 1px solid #000;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        .totals-table {
            width: 30%;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        .totals-header {
            background-color: #666;
            color: white;
            text-align: center;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .footer-table td {
            border: 1px solid #000;
            padding: 10px;
            vertical-align: top;
        }
        .qr-code {
            width: 150px;
            height: 150px;
        }
        .email-link {
            color: #0000EE;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td width="50%">
                <img src="https://i.postimg.cc/0yM3rkgN/logo-dise-o.png" alt="Logo" class="logo">
            </td>
            <td width="50%">
                <div class="company-info">
                    Razón Social: {{ $invoice->company->business_name }}<br>
                    NIT: {{ $invoice->company->document_number }}<br>
                    Dirección: {{ $invoice->company->address }}<br>
                    Ciudad: {{ $invoice->company->city }}<br>
                    Correo Electrónico: <span class="email-link">{{ $invoice->company->email }}</span><br>
                    Teléfono: {{ $invoice->company->phone }}
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td width="50%">
                <div class="info-header">Cliente</div>
                Razón Social: {{ $invoice->customer->business_name }}<br>
                NIT: {{ $invoice->customer->document_number }}<br>
                Dirección: {{ $invoice->customer->address }}<br>
                Ciudad: {{ $invoice->customer->city }}<br>
                Correo Electrónico: <span class="email-link">{{ $invoice->customer->email }}</span><br>
                Teléfono: {{ $invoice->customer->phone }}
            </td>
            <td width="50%">
                <div class="info-header">Factura Electrónica de Venta</div>
                Número: {{ $invoice->prefix }}-{{ $invoice->number }}<br>
                Fecha de Expedición: {{ optional($invoice->issue_date)->format('Y-m-d') ?? 'N/A' }}<br>
                Fecha de Vencimiento: {{ optional($invoice->payment_due_date)->format('Y-m-d') ?? 'N/A' }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Cantidad</th>
                <th>Descripción</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
            <tr>
                <td>{{ number_format($line->quantity, 0) }}</td>
                <td style="text-align: left;">{{ $line->description }}</td>
                <td>${{ number_format($line->price, 2, ',', '.') }}</td>
                <td>${{ number_format($line->subtotal, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="totals-header" colspan="2">Precio Unitario</td>
            <td class="totals-header">Totales</td>
        </tr>
        <tr>
            <td colspan="2">SUBTOTAL</td>
            <td>${{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2">DESCUENTO</td>
            <td>${{ number_format($invoice->total_discount, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2">IVA 19.00%</td>
            <td>${{ number_format($invoice->total_tax, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2">TOTAL</td>
            <td>${{ number_format($invoice->total_amount, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <table class="footer-table">
        <tr>
            <td width="20%">
                <img src="{{ $qrPath }}" alt="QR Code" class="qr-code">
            </td>
            <td width="80%">
                <strong>Notas:</strong><br>
                {{ $invoice->notes }}<br><br>
                <strong>CUFE:</strong><br>
                {{ $invoice->cufe }}<br><br>
                <strong>Resolución de Facturación No:</strong> {{ $invoice->resolution->number }} del {{ optional($invoice->resolution->date)->format('Y-m-d') ?? 'N/A' }}<br>
                Rangos de numeración desde: {{ $invoice->resolution->from }}, hasta: {{ $invoice->resolution->to }}<br>
                Vigencia: desde {{ optional($invoice->resolution->start_date)->format('Y-m-d') ?? 'N/A' }}, hasta: {{ optional($invoice->resolution->end_date)->format('Y-m-d') ?? 'N/A' }}
            </td>
        </tr>
    </table>
</body>
</html>
