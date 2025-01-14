<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Débito {{ $debitNote->prefix }}-{{ $debitNote->number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .company-info {
            float: left;
            width: 60%;
        }
        .document-info {
            float: right;
            width: 35%;
            text-align: right;
        }
        .clear {
            clear: both;
        }
        .customer-info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .totals {
            float: right;
            width: 35%;
        }
        .totals table {
            width: 100%;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h2>{{ $company->business_name }}</h2>
            <p>NIT: {{ $company->tax_id }}</p>
            <p>{{ $branch->address }}</p>
            <p>Tel: {{ $branch->phone }}</p>
        </div>
        <div class="document-info">
            <h1>NOTA DÉBITO</h1>
            <p>No. {{ $debitNote->prefix }}-{{ $debitNote->number }}</p>
            <p>Fecha: {{ $debitNote->issue_date->format('Y-m-d') }}</p>
            <p>CUFE: {{ $debitNote->cufe }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="customer-info">
        <h3>Cliente</h3>
        <p>{{ $customer->business_name }}</p>
        <p>NIT: {{ $customer->tax_id }}</p>
        <p>{{ $customer->address }}</p>
        <p>Tel: {{ $customer->phone }}</p>
    </div>

    <div class="invoice-info">
        <h3>Factura Relacionada</h3>
        <p>No. {{ $debitNote->invoice->prefix }}-{{ $debitNote->invoice->number }}</p>
        <p>Concepto de Corrección: {{ $debitNote->correction_concept }}</p>
        <p>Notas: {{ $debitNote->notes }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Unidad</th>
                <th>Precio Unit.</th>
                <th>Desc %</th>
                <th>IVA %</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
            <tr>
                <td>{{ optional($line->product)->code }}</td>
                <td>{{ $line->description }}</td>
                <td>{{ number_format($line->quantity, 2) }}</td>
                <td>UN</td>
                <td>{{ number_format($line->price, 2) }}</td>
                <td>{{ number_format($line->discount_rate, 2) }}%</td>
                <td>19%</td>
                <td>{{ number_format($line->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <th>Subtotal</th>
                <td>{{ number_format($debitNote->subtotal, 2) }}</td>
            </tr>
            <tr>
                <th>Descuento</th>
                <td>{{ number_format($debitNote->total_discount, 2) }}</td>
            </tr>
            <tr>
                <th>IVA</th>
                <td>{{ number_format($debitNote->total_tax, 2) }}</td>
            </tr>
            <tr>
                <th>Total</th>
                <td>{{ number_format($debitNote->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="clear"></div>

    <div class="footer">
        <p>Esta nota débito es un título valor según el artículo 772 del Código de Comercio</p>
        <p>Generada por Software {{ config('app.name') }} - {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
