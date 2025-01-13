<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoicing\Invoice;

class PdfService
{
    private $invoice;
    private $qrContent;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->generateQrContent();
    }

    private function generateQrContent()
    {
        // Según DIAN, el QR debe contener:
        // NumFac, FecFac, HorFac, NitFac, DocAdq, ValFac, ValIva, ValOtroIm, ValTolFac, CUFE/CUDE
        $this->qrContent = implode('|', [
            $this->invoice->number,                    // NumFac
            $this->invoice->issue_date->format('Y-m-d'),    // FecFac
            $this->invoice->issue_date->format('H:i:s'),    // HorFac
            $this->invoice->company->document_number,  // NitFac
            $this->invoice->customer->document_number, // DocAdq
            number_format($this->invoice->subtotal, 2, '.', ''), // ValFac
            number_format($this->invoice->total_tax, 2, '.', ''), // ValIva
            '0.00',                                   // ValOtroIm
            number_format($this->invoice->total_amount, 2, '.', ''), // ValTolFac
            $this->invoice->cufe                      // CUFE/CUDE
        ]);
    }

    private function getInvoicePath($type)
    {
        $basePath = storage_path('app/public/invoices/' . $this->invoice->id . '/' . $type);
        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, true);
        }
        return $basePath;
    }

    public function generatePdf()
    {
        try {
            // Generar el código QR
            $qrPath = $this->getInvoicePath('qr');
            $qrFile = $qrPath . '/qr.svg';
            QrCode::format('svg')
                ->size(200)
                ->margin(1)
                ->generate($this->qrContent, $qrFile);

            // Generar el PDF
            $data = [
                'invoice' => $this->invoice,
                'qrPath' => $qrFile,
                'qrContent' => $this->qrContent
            ];

            $pdf = PDF::loadView('pdf.invoice', $data);
            $pdf->setPaper('letter');

            // Guardar el PDF
            $pdfPath = $this->getInvoicePath('pdf');
            $pdfFile = $pdfPath . '/SETP-' . $this->invoice->number . '.pdf';
            $pdf->save($pdfFile);

            return [
                'success' => true,
                'message' => 'PDF generated successfully',
                'data' => [
                    'filename' => 'invoices/' . $this->invoice->id . '/pdf/SETP-' . $this->invoice->number . '.pdf',
                    'url' => '/storage/invoices/' . $this->invoice->id . '/pdf/SETP-' . $this->invoice->number . '.pdf'
                ]
            ];
        } catch (\Exception $e) {
            logger('Error generating PDF: ' . $e->getMessage(), [
                'invoice_id' => $this->invoice->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error generating PDF',
                'error' => $e->getMessage()
            ];
        }
    }
}
