<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice->number }}</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --border-color: #e2e8f0;
            --background-color: #f8fafc;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            background-color: white;
        }

        .document-title {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .company-info {
            flex: 1;
        }

        .invoice-info {
            text-align: right;
            flex: 1;
        }

        .company-name {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .info-block {
            background-color: var(--background-color);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .info-block h3 {
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            background-color: white;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        tr:nth-child(even) {
            background-color: var(--background-color);
        }

        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .totals-table {
            width: 350px;
            margin-left: auto;
        }

        .totals-table td {
            padding: 8px 12px;
        }

        .totals-table tr:last-child {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
        }

        .qr-section {
            text-align: center;
            margin: 40px 0;
            padding: 20px;
            background-color: var(--background-color);
            border-radius: 8px;
        }

        .qr-section img {
            max-width: 150px;
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
            text-align: center;
            color: var(--secondary-color);
            font-size: 12px;
        }

        .footer p {
            margin: 5px 0;
        }

        .highlight {
            color: var(--primary-color);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="document-title">Factura Electrónica</div>

    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ $invoice->company->business_name }}</div>
            <p><span class="highlight">NIT:</span> {{ $invoice->company->document_number }}-{{ $invoice->company->dv }}</p>
            <p>{{ $invoice->company->address }}</p>
            <p>{{ $invoice->company->location->name }}, {{ $invoice->company->location->department->name }}</p>
            <p><span class="highlight">Tel:</span> {{ $invoice->company->phone }}</p>
            <p><span class="highlight">Email:</span> {{ $invoice->company->email }}</p>
        </div>
        <div class="invoice-info">
            <h2>Factura No. {{ $invoice->number }}</h2>
            <p><span class="highlight">Fecha de Emisión:</span><br>{{ $invoice->issue_date->format('Y-m-d H:i:s') }}</p>
            <p><span class="highlight">Fecha de Vencimiento:</span><br>{{ $invoice->payment_due_date->format('Y-m-d') }}</p>
            <p><span class="highlight">CUFE:</span><br>{{ $invoice->cufe }}</p>
        </div>
    </div>

    <div class="info-block">
        <h3>Información del Cliente</h3>
        <div class="info-grid">
            <div>
                <p><span class="highlight">Razón Social:</span><br>{{ $invoice->customer->business_name }}</p>
                <p><span class="highlight">NIT:</span><br>{{ $invoice->customer->document_number }}-{{ $invoice->customer->dv }}</p>
                <p><span class="highlight">Dirección:</span><br>{{ $invoice->customer->address }}</p>
            </div>
            <div>
                <p><span class="highlight">Ciudad:</span><br>{{ $invoice->customer->location->name }}, {{ $invoice->customer->location->department->name }}</p>
                <p><span class="highlight">Teléfono:</span><br>{{ $invoice->customer->phone }}</p>
                <p><span class="highlight">Email:</span><br>{{ $invoice->customer->email }}</p>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 45%">Descripción</th>
                <th style="width: 10%">Cantidad</th>
                <th style="width: 15%">Valor Unit.</th>
                <th style="width: 10%">IVA</th>
                <th style="width: 15%">Total</th>
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

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td><span class="highlight">Subtotal:</span></td>
                <td>${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td><span class="highlight">Descuentos:</span></td>
                <td>${{ number_format($invoice->total_discount, 2) }}</td>
            </tr>
            <tr>
                <td><span class="highlight">IVA:</span></td>
                <td>${{ number_format($invoice->total_tax, 2) }}</td>
            </tr>
            <tr>
                <td>Total a Pagar:</td>
                <td>${{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="qr-section">
        <img src="{{ $qrPath }}" alt="QR Code">
        <p>Escanee este código QR para verificar la factura electrónica</p>
    </div>

    <div class="footer">
        <p><strong>Esta factura electrónica cumple con los requisitos de la DIAN</strong></p>
        <p>Resolución DIAN No. {{ $invoice->company->resolution->resolution }} de {{ $invoice->company->resolution->resolution_date->format('Y-m-d') }}</p>
        <p>Rango autorizado: {{ $invoice->company->resolution->from }} - {{ $invoice->company->resolution->to }}</p>
        <p>Vigencia: {{ $invoice->company->resolution->resolution_date->format('Y-m-d') }} hasta {{ $invoice->company->resolution->expiration_date->format('Y-m-d') }}</p>
    </div>
</body>
</html>
