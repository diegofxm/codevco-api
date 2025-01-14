<?php

namespace App\Services\Xml;

use App\Models\Invoicing\CreditNote;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class CreditNoteXmlService
{
    private $creditNote;
    private $xml;

    public function __construct(CreditNote $creditNote)
    {
        $this->creditNote = $creditNote;
        $this->xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><CreditNote></CreditNote>');
    }

    public function generate()
    {
        try {
            // Atributos principales
            $this->xml->addAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2');
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

            // Información de la nota crédito
            $this->xml->addChild('cbc:UBLVersionID', '2.1');
            $this->xml->addChild('cbc:CustomizationID', '10');
            $this->xml->addChild('cbc:ProfileID', 'DIAN 2.1');
            $this->xml->addChild('cbc:ProfileExecutionID', '1');
            $this->xml->addChild('cbc:ID', $this->creditNote->number);
            $this->xml->addChild('cbc:UUID', $this->creditNote->cufe);
            $this->xml->addChild('cbc:IssueDate', $this->creditNote->issue_date->format('Y-m-d'));
            $this->xml->addChild('cbc:IssueTime', $this->creditNote->issue_date->format('H:i:s'));
            $this->xml->addChild('cbc:CreditNoteTypeCode', '91');
            $this->xml->addChild('cbc:DocumentCurrencyCode', 'COP');

            // Notas y concepto de corrección
            if ($this->creditNote->notes) {
                $this->xml->addChild('cbc:Note', $this->creditNote->notes);
            }

            // Referencia a la factura original
            $billingReference = $this->xml->addChild('cac:BillingReference');
            $invoiceDocumentReference = $billingReference->addChild('cac:InvoiceDocumentReference');
            $invoiceDocumentReference->addChild('cbc:ID', $this->creditNote->invoice->number);
            $invoiceDocumentReference->addChild('cbc:UUID', $this->creditNote->invoice->cufe);
            $invoiceDocumentReference->addChild('cbc:IssueDate', $this->creditNote->invoice->issue_date->format('Y-m-d'));

            // Motivo de la nota crédito
            $discrepancyResponse = $this->xml->addChild('cac:DiscrepancyResponse');
            $discrepancyResponse->addChild('cbc:ReferenceID', $this->creditNote->invoice->number);
            $discrepancyResponse->addChild('cbc:ResponseCode', $this->creditNote->discrepancy_code);
            $discrepancyResponse->addChild('cbc:Description', $this->creditNote->correction_concept);

            // Información del emisor
            $AccountingSupplierParty = $this->xml->addChild('cac:AccountingSupplierParty');
            $Party = $AccountingSupplierParty->addChild('cac:Party');
            $PartyName = $Party->addChild('cac:PartyName');
            $PartyName->addChild('cbc:Name', $this->creditNote->invoice->company->business_name);

            // Información del cliente
            $AccountingCustomerParty = $this->xml->addChild('cac:AccountingCustomerParty');
            $Party = $AccountingCustomerParty->addChild('cac:Party');
            $PartyName = $Party->addChild('cac:PartyName');
            $PartyName->addChild('cbc:Name', $this->creditNote->invoice->customer->business_name);

            // Líneas de la nota crédito
            $this->addItems($this->xml, $this->creditNote);

            // Totales
            $LegalMonetaryTotal = $this->xml->addChild('cac:LegalMonetaryTotal');
            $LegalMonetaryTotal->addChild('cbc:LineExtensionAmount', number_format($this->creditNote->subtotal, 2, '.', ''))->addAttribute('currencyID', 'COP');
            $LegalMonetaryTotal->addChild('cbc:TaxExclusiveAmount', number_format($this->creditNote->subtotal - $this->creditNote->total_discount, 2, '.', ''))->addAttribute('currencyID', 'COP');
            $LegalMonetaryTotal->addChild('cbc:TaxInclusiveAmount', number_format($this->creditNote->total_amount, 2, '.', ''))->addAttribute('currencyID', 'COP');
            $LegalMonetaryTotal->addChild('cbc:PayableAmount', number_format($this->creditNote->total_amount, 2, '.', ''))->addAttribute('currencyID', 'COP');

            // Generar el archivo XML
            $filename = sprintf(
                'credit-notes/%s/xml/%s-%s.xml',
                $this->creditNote->id,
                $this->creditNote->prefix,
                $this->creditNote->number
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
            logger()->error('Error generating credit note XML: ' . $e->getMessage(), [
                'credit_note_id' => $this->creditNote->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error generating XML',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function addItems($xml, $creditNote)
    {
        $items = $creditNote->lines;

        foreach ($items as $item) {
            $itemElement = $xml->addChild('cac:CreditNoteLine');
            $itemElement->addChild('cbc:ID', $item->id);
            $itemElement->addChild('cbc:CreditedQuantity', $item->quantity)->addAttribute('unitCode', $item->unitMeasure->code);
            $itemElement->addChild('cbc:LineExtensionAmount', number_format($item->subtotal, 2, '.', ''))->addAttribute('currencyID', 'COP');
            
            // Información del producto
            $Item = $itemElement->addChild('cac:Item');
            $Item->addChild('cbc:Description', $item->description);
            $Item->addChild('cbc:ModelName', $item->product->name);
            
            // Precio
            $Price = $itemElement->addChild('cac:Price');
            $Price->addChild('cbc:PriceAmount', number_format($item->price, 2, '.', ''))->addAttribute('currencyID', 'COP');
            
            // Impuestos
            if ($item->tax_amount > 0) {
                $TaxTotal = $itemElement->addChild('cac:TaxTotal');
                $TaxTotal->addChild('cbc:TaxAmount', number_format($item->tax_amount, 2, '.', ''))->addAttribute('currencyID', 'COP');
                $TaxSubtotal = $TaxTotal->addChild('cac:TaxSubtotal');
                $TaxSubtotal->addChild('cbc:TaxableAmount', number_format($item->subtotal, 2, '.', ''))->addAttribute('currencyID', 'COP');
                $TaxSubtotal->addChild('cbc:TaxAmount', number_format($item->tax_amount, 2, '.', ''))->addAttribute('currencyID', 'COP');
                $TaxSubtotal->addChild('cbc:Percent', number_format($item->tax->rate, 2, '.', ''));
            }
        }
    }
}
