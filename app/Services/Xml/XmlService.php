<?php

namespace App\Services\Xml;

use App\Models\Invoicing\Invoice;
use Illuminate\Support\Facades\Storage;

class XmlService
{
    private function getInvoicePath($invoice)
    {
        $basePath = 'public/invoices/' . $invoice->id . '/xml';
        if (!Storage::exists($basePath)) {
            Storage::makeDirectory($basePath);
        }
        return $basePath;
    }

    public function generateInvoiceXml(Invoice $invoice): string
    {
        $generator = new UblGenerator($invoice);
        $xml = $generator->generate();
        
        // Guardar el XML
        $filename = $this->getXmlFilename($invoice);
        Storage::put($filename, $xml->saveXML());
        
        return $filename;
    }
    
    private function getXmlFilename(Invoice $invoice): string
    {
        $path = $this->getInvoicePath($invoice);
        return sprintf(
            '%s/SETP-%s.xml',
            $path,
            $invoice->number
        );
    }
}
