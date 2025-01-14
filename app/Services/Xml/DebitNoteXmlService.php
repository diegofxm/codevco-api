<?php

namespace App\Services\Xml;

use App\Models\Invoicing\DebitNote;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class DebitNoteXmlService
{
    private $debitNote;
    private $xml;

    public function __construct(DebitNote $debitNote)
    {
        $this->debitNote = $debitNote;
        $this->xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><DebitNote></DebitNote>');
    }

    public function generate()
    {
        try {
            // Atributos principales
            $this->xml->addAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:DebitNote-2');
            $this->xml->addAttribute('xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            $this->xml->addAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $this->xml->addAttribute('xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
            $this->xml->addAttribute('xmlns:ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
            $this->xml->addAttribute('xmlns:sts', 'dian:gov:co:facturaelectronica:Structures-2-1');
            $this->xml->addAttribute('xmlns:xades', 'http://uri.etsi.org/01903/v1.3.2#');
            $this->xml->addAttribute('xmlns:xades141', 'http://uri.etsi.org/01903/v1.4.1#');
            $this->xml->addAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

            // UBLExtensions
            $UBLExtensions = $this->xml->addChild('ext:UBLExtensions');
            $UBLExtension = $UBLExtensions->addChild('ext:UBLExtension');
            $ExtensionContent = $UBLExtension->addChild('ext:ExtensionContent');

            // Información de la nota débito
            $this->xml->addChild('cbc:UBLVersionID', '2.1');
            $this->xml->addChild('cbc:CustomizationID', '10');
            $this->xml->addChild('cbc:ProfileID', 'DIAN 2.1');
            $this->xml->addChild('cbc:ProfileExecutionID', '1');
            $this->xml->addChild('cbc:ID', $this->debitNote->number);
            $this->xml->addChild('cbc:UUID', $this->debitNote->cufe);
            $this->xml->addChild('cbc:IssueDate', $this->debitNote->issue_date->format('Y-m-d'));
            $this->xml->addChild('cbc:IssueTime', $this->debitNote->issue_date->format('H:i:s'));
            $this->xml->addChild('cbc:DebitNoteTypeCode', '92');
            $this->xml->addChild('cbc:DocumentCurrencyCode', 'COP');

            // Información del emisor
            $AccountingSupplierParty = $this->xml->addChild('cac:AccountingSupplierParty');
            $Party = $AccountingSupplierParty->addChild('cac:Party');
            $PartyName = $Party->addChild('cac:PartyName');
            $PartyName->addChild('cbc:Name', $this->debitNote->invoice->company->business_name);

            // Información del cliente
            $AccountingCustomerParty = $this->xml->addChild('cac:AccountingCustomerParty');
            $Party = $AccountingCustomerParty->addChild('cac:Party');
            $PartyName = $Party->addChild('cac:PartyName');
            $PartyName->addChild('cbc:Name', $this->debitNote->invoice->customer->business_name);

            // Totales
            $LegalMonetaryTotal = $this->xml->addChild('cac:LegalMonetaryTotal');
            $LegalMonetaryTotal->addChild('cbc:LineExtensionAmount', number_format($this->debitNote->subtotal, 2, '.', ''))->addAttribute('currencyID', 'COP');
            $LegalMonetaryTotal->addChild('cbc:TaxExclusiveAmount', number_format($this->debitNote->subtotal - $this->debitNote->total_discount, 2, '.', ''))->addAttribute('currencyID', 'COP');
            $LegalMonetaryTotal->addChild('cbc:TaxInclusiveAmount', number_format($this->debitNote->total_amount, 2, '.', ''))->addAttribute('currencyID', 'COP');
            $LegalMonetaryTotal->addChild('cbc:PayableAmount', number_format($this->debitNote->total_amount, 2, '.', ''))->addAttribute('currencyID', 'COP');

            // Generar el archivo XML
            $filename = sprintf(
                'debit-notes/%s/xml/%s-%s.xml',
                $this->debitNote->id,
                $this->debitNote->prefix,
                $this->debitNote->number
            );

            Storage::put('public/' . $filename, $this->xml->asXML());

            return [
                'success' => true,
                'message' => 'XML generated successfully',
                'data' => [
                    'filename' => $filename,
                    'url' => '/storage/' . $filename
                ]
            ];
        } catch (\Exception $e) {
            logger()->error('Error generating debit note XML: ' . $e->getMessage(), [
                'debit_note_id' => $this->debitNote->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error generating XML',
                'error' => $e->getMessage()
            ];
        }
    }
}
