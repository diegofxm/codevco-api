<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoicing\DebitNote;

class DebitNotePdfService
{
    private $debitNote;

    public function __construct(DebitNote $debitNote)
    {
        $this->debitNote = $debitNote;
    }

    private function getDebitNotePath($type)
    {
        $basePath = storage_path('app/public/debit-notes/' . $this->debitNote->id . '/' . $type);
        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, true);
        }
        return $basePath;
    }

    public function generate()
    {
        try {
            // Cargar relaciones necesarias
            $this->debitNote->load([
                'invoice',
                'invoice.company',
                'invoice.customer',
                'invoice.branch',
                'invoice.currency',
                'lines.unitMeasure',
                'lines.product',
                'lines.tax',
                'resolution'
            ]);

            // Preparar datos para la vista
            $data = [
                'debitNote' => $this->debitNote,
                'company' => $this->debitNote->invoice->company,
                'customer' => $this->debitNote->invoice->customer,
                'branch' => $this->debitNote->invoice->branch,
                'currency' => $this->debitNote->invoice->currency,
                'lines' => $this->debitNote->lines,
                'resolution' => $this->debitNote->resolution
            ];

            // Generar el PDF
            $pdf = PDF::loadView('pdfs.debit-note', $data);
            $pdf->setPaper('letter');

            // Guardar el PDF
            $pdfPath = $this->getDebitNotePath('pdf');
            $pdfFile = $pdfPath . '/SETP-' . $this->debitNote->number . '.pdf';
            $pdf->save($pdfFile);

            return [
                'success' => true,
                'message' => 'PDF generated successfully',
                'data' => [
                    'filename' => 'debit-notes/' . $this->debitNote->id . '/pdf/SETP-' . $this->debitNote->number . '.pdf',
                    'url' => '/storage/debit-notes/' . $this->debitNote->id . '/pdf/SETP-' . $this->debitNote->number . '.pdf'
                ]
            ];
        } catch (\Exception $e) {
            logger('Error generating PDF: ' . $e->getMessage(), [
                'debit_note_id' => $this->debitNote->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error generating PDF',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function make(DebitNote $debitNote)
    {
        return new self($debitNote);
    }
}
