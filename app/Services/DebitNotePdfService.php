<?php

namespace App\Services;

use App\Models\Invoicing\DebitNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DebitNotePdfService
{
    public function generate(DebitNote $debitNote)
    {
        // Cargar las relaciones necesarias
        $debitNote->load([
            'invoice.company',
            'invoice.customer',
            'invoice.branch',
            'invoice.currency',
            'lines.product'
        ]);

        // Generar el PDF
        $pdf = PDF::loadView('pdfs.debit-note', [
            'debitNote' => $debitNote,
            'company' => $debitNote->invoice->company,
            'customer' => $debitNote->invoice->customer,
            'branch' => $debitNote->invoice->branch,
            'lines' => $debitNote->lines
        ]);

        // Crear el directorio si no existe
        $directory = "debit-notes/{$debitNote->id}/pdf";
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Guardar el PDF
        $filename = "{$debitNote->prefix}-{$debitNote->number}.pdf";
        $path = "{$directory}/{$filename}";
        Storage::put($path, $pdf->output());

        return [
            'filename' => $path,
            'url' => "/storage/{$path}"
        ];
    }
}
