<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $invoice->number }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fa;
        }
        .container {
            width: 800px;
            margin: 40px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        h1, h2, h3, h4 {
            font-family: 'Arial', sans-serif;
            margin-bottom: 10px;
            color: #333;
        }
        h1 {
            font-size: 32px;
            color: #007bff;
        }
        h2 {
            font-size: 24px;
            color: #555;
        }
        .company-info, .customer-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .company-info div, .customer-info div {
            width: 48%;
        }
        .company-info p, .customer-info p {
            margin: 0;
            font-size: 14px;
            color: #555;
        }
        .invoice-details {
            border-top: 2px solid #007bff;
            padding-top: 15px;
            margin-bottom: 25px;
        }
        .invoice-details p {
            font-size: 16px;
            margin: 5px 0;
        }
        .table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .table th {
            background-color: #f1f1f1;
            color: #333;
        }
        .table td {
            color: #555;
        }
        .totals {
            text-align: right;
            font-size: 16px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 8px;
            font-weight: bold;
            color: #333;
        }
        .totals td:first-child {
            text-align: left;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #999;
            margin-top: 30px;
            border-top: 2px solid #ddd;
            padding-top: 15px;
        }
        .qr-container {
            text-align: center;
            margin-top: 20px;
        }
        .qr-container img {
            max-width: 120px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="company-info">
        <div>
            <h1>{{ $invoice->company->business_name }}</h1>
            <p><strong>NIT:</strong> {{ $invoice->company->document_number }}-{{ $invoice->company->dv }}</p>
            <p>{{ $invoice->company->address }}</p>
            <p>{{ $invoice->company->location->name }}, {{ $invoice->company->location->department->name }}</p>
        </div>
        <div style="text-align: right;">
            <h2>Factura No. {{ $invoice->number }}</h2>
            <p><strong>Fecha:</strong> {{ $invoice->issue_date->format('Y-m-d H:i:s') }}</p>
            <p><strong>Vencimiento:</strong> {{ $invoice->payment_due_date->format('Y-m-d') }}</p>
            <p><strong>CUFE:</strong> {{ $invoice->cufe }}</p>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="customer-info">
        <div>
            <h4>Datos del Cliente</h4>
            <p><strong>Razón Social:</strong> {{ $invoice->customer->business_name }}</p>
            <p><strong>NIT:</strong> {{ $invoice->customer->document_number }}-{{ $invoice->customer->dv }}</p>
            <p><strong>Dirección:</strong> {{ $invoice->customer->address }}</p>
        </div>
        <div>
            <h4>&nbsp;</h4>
            <p><strong>Ciudad:</strong> {{ $invoice->customer->location->name }}, {{ $invoice->customer->location->department->name }}</p>
            <p><strong>Tel:</strong> {{ $invoice->customer->phone }}</p>
            <p><strong>Email:</strong> {{ $invoice->customer->email }}</p>
        </div>
    </div>

    <!-- Invoice Items -->
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Valor Unitario</th>
                <th>IVA</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->line }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ number_format($item->quantity, 2) }}</td>
                <td>${{ number_format($item->price, 2) }}</td>
                <td>{{ $item->tax_rate }}%</td>
                <td>${{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <table>
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td>${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Descuentos:</strong></td>
                <td>${{ number_format($invoice->total_discount, 2) }}</td>
            </tr>
            <tr>
                <td><strong>IVA:</strong></td>
                <td>${{ number_format($invoice->total_tax, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total:</strong></td>
                <td>${{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- QR Code -->
    <div class="qr-container">
        <img src="{{ $qrPath }}" alt="QR Code">
        <p>Código QR de la factura electrónica</p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Esta factura electrónica cumple con los requisitos de la DIAN</p>
        <p>Resolución DIAN No. {{ $invoice->company->resolution->resolution }} de {{ $invoice->company->resolution->resolution_date->format('Y-m-d') }}</p>
        <p>Rango autorizado: {{ $invoice->company->resolution->from }} - {{ $invoice->company->resolution->to }}</p>
        <p>Vigencia: {{ $invoice->company->resolution->resolution_date->format('Y-m-d') }} hasta {{ $invoice->company->resolution->expiration_date->format('Y-m-d') }}</p>
    </div>
</div>

</body>
</html>
