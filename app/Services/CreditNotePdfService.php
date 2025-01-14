<?php

namespace App\Services;

use App\Models\Invoicing\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CreditNotePdfService
{
    public function generate(CreditNote $creditNote)
    {
        // Cargar las relaciones necesarias
        $creditNote->load([
            'invoice.company',
            'invoice.customer',
            'invoice.branch',
            'invoice.currency',
            'lines.product'
        ]);

        // Generar el PDF
        $pdf = PDF::loadView('pdfs.credit-note', [
            'creditNote' => $creditNote,
            'company' => $creditNote->invoice->company,
            'customer' => $creditNote->invoice->customer,
            'branch' => $creditNote->invoice->branch,
            'lines' => $creditNote->lines
        ]);

        // Crear el directorio si no existe
        $directory = "credit-notes/{$creditNote->id}/pdf";
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Guardar el PDF
        $filename = "{$creditNote->prefix}-{$creditNote->number}.pdf";
        $path = "{$directory}/{$filename}";
        Storage::put($path, $pdf->output());

        return [
            'filename' => $path,
            'url' => "/storage/{$path}"
        ];
    }
}
