<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoicing\CreditNote;

class CreditNotePdfService
{
    private $creditNote;

    public function __construct(CreditNote $creditNote)
    {
        $this->creditNote = $creditNote;
    }

    private function getCreditNotePath($type)
    {
        $basePath = storage_path('app/public/credit-notes/' . $this->creditNote->id . '/' . $type);
        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, true);
        }
        return $basePath;
    }

    public function generate()
    {
        try {
            // Cargar relaciones necesarias
            $this->creditNote->load([
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
                'creditNote' => $this->creditNote,
                'company' => $this->creditNote->invoice->company,
                'customer' => $this->creditNote->invoice->customer,
                'branch' => $this->creditNote->invoice->branch,
                'currency' => $this->creditNote->invoice->currency,
                'lines' => $this->creditNote->lines,
                'resolution' => $this->creditNote->resolution
            ];

            // Generar el PDF
            $pdf = PDF::loadView('pdfs.credit-note', $data);
            $pdf->setPaper('letter');

            // Guardar el PDF
            $pdfPath = $this->getCreditNotePath('pdf');
            $pdfFile = $pdfPath . '/SETP-' . $this->creditNote->number . '.pdf';
            $pdf->save($pdfFile);

            return [
                'success' => true,
                'message' => 'PDF generated successfully',
                'data' => [
                    'filename' => 'credit-notes/' . $this->creditNote->id . '/pdf/SETP-' . $this->creditNote->number . '.pdf',
                    'url' => '/storage/credit-notes/' . $this->creditNote->id . '/pdf/SETP-' . $this->creditNote->number . '.pdf'
                ]
            ];
        } catch (\Exception $e) {
            logger('Error generating PDF: ' . $e->getMessage(), [
                'credit_note_id' => $this->creditNote->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error generating PDF',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function make(CreditNote $creditNote)
    {
        return new self($creditNote);
    }
}
