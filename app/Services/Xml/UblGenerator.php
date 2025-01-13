<?php

namespace App\Services\Xml;

use DOMDocument;
use DOMElement;
use App\Models\Invoicing\Invoice;

class UblGenerator
{
    private $dom;
    private $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = true;
    }

    public function generate(): DOMDocument
    {
        try {
            // Crear documento XML
            $this->dom = new DOMDocument('1.0', 'UTF-8');
            $this->dom->preserveWhiteSpace = false;
            $this->dom->formatOutput = true;

            // Crear elemento raíz Invoice
            $root = $this->dom->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:Invoice-2', 'Invoice');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:sts', 'dian:gov:co:facturaelectronica:Structures-2-1');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xades', 'http://uri.etsi.org/01903/v1.3.2#');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xades141', 'http://uri.etsi.org/01903/v1.4.1#');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

            // UBLExtensions (requerido para la firma digital)
            $extensions = $this->dom->createElement('ext:UBLExtensions');
            $extension = $this->dom->createElement('ext:UBLExtension');
            $extensionContent = $this->dom->createElement('ext:ExtensionContent');
            $extension->appendChild($extensionContent);
            $extensions->appendChild($extension);
            $root->appendChild($extensions);

            // Información de Control
            $this->addNode($root, 'cbc:UBLVersionID', '2.1');
            $this->addNode($root, 'cbc:CustomizationID', '10');
            $this->addNode($root, 'cbc:ProfileID', 'DIAN 2.1');
            $this->addNode($root, 'cbc:ProfileExecutionID', $this->invoice->company->environment);
            $this->addNode($root, 'cbc:ID', $this->invoice->number);
            $this->addNode($root, 'cbc:UUID', $this->invoice->cufe, [
                'schemeID' => $this->invoice->company->environment,
                'schemeName' => '2'
            ]);
            $this->addNode($root, 'cbc:IssueDate', $this->invoice->issue_date->format('Y-m-d'));
            $this->addNode($root, 'cbc:IssueTime', $this->invoice->issue_date->format('H:i:s'));
            $this->addNode($root, 'cbc:DueDate', $this->invoice->payment_due_date->format('Y-m-d'));
            $this->addNode($root, 'cbc:InvoiceTypeCode', '01');
            $this->addNode($root, 'cbc:Note', $this->invoice->notes);
            $this->addNode($root, 'cbc:DocumentCurrencyCode', $this->invoice->currency->code);

            // Información del Vendedor y Cliente
            $this->addSupplierParty($root);
            $this->addCustomerParty($root);

            // Información de Impuestos
            $this->addTaxTotals($root);

            // Totales Legales Monetarios
            $this->addLegalMonetaryTotal($root);

            // Líneas de Factura
            $this->addInvoiceLines($root);

            $this->dom->appendChild($root);
            return $this->dom;
        } catch (\Exception $e) {
            \Log::error('Error generating XML: ' . $e->getMessage(), [
                'invoice_id' => $this->invoice->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function addSupplierParty($root)
    {
        try {
            $supplierParty = $this->dom->createElement('cac:AccountingSupplierParty');
            $this->addNode($supplierParty, 'cbc:AdditionalAccountID', $this->invoice->company->typeOrganization->code);

            $party = $this->dom->createElement('cac:Party');
            
            // Dirección física
            $physicalLocation = $this->dom->createElement('cac:PhysicalLocation');
            $address = $this->dom->createElement('cac:Address');
            $this->addNode($address, 'cbc:ID', $this->invoice->company->location->code);
            $this->addNode($address, 'cbc:CityName', $this->invoice->company->location->name);
            $this->addNode($address, 'cbc:CountrySubentity', $this->invoice->company->location->department->name);
            $this->addNode($address, 'cbc:CountrySubentityCode', $this->invoice->company->location->department->code);
            $this->addNode($address, 'cac:AddressLine/cbc:Line', $this->invoice->company->address);
            $physicalLocation->appendChild($address);
            $party->appendChild($physicalLocation);

            // Información legal y tributaria
            $partyTaxScheme = $this->dom->createElement('cac:PartyTaxScheme');
            $this->addNode($partyTaxScheme, 'cbc:RegistrationName', $this->invoice->company->business_name);
            
            // Identificación de la empresa
            $companyId = $this->dom->createElement('cbc:CompanyID');
            $textNode = $this->dom->createTextNode($this->invoice->company->document_number);
            $companyId->appendChild($textNode);
            $companyId->setAttribute('schemeID', $this->invoice->company->dv);
            $companyId->setAttribute('schemeName', $this->invoice->company->typeDocument->code);
            $companyId->setAttribute('schemeAgencyID', '195');
            $companyId->setAttribute('schemeAgencyName', 'CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)');
            $partyTaxScheme->appendChild($companyId);

            // Régimen fiscal
            $taxScheme = $this->dom->createElement('cac:TaxScheme');
            $this->addNode($taxScheme, 'cbc:ID', $this->invoice->company->typeRegime->code);
            $this->addNode($taxScheme, 'cbc:Name', $this->invoice->company->typeRegime->name);
            $partyTaxScheme->appendChild($taxScheme);
            
            $party->appendChild($partyTaxScheme);

            // Información de contacto
            $contact = $this->dom->createElement('cac:Contact');
            $this->addNode($contact, 'cbc:Telephone', $this->invoice->company->phone);
            $this->addNode($contact, 'cbc:ElectronicMail', $this->invoice->company->email);
            $party->appendChild($contact);

            $supplierParty->appendChild($party);
            $root->appendChild($supplierParty);
        } catch (\Exception $e) {
            \Log::error('Error adding supplier party: ' . $e->getMessage(), [
                'invoice_id' => $this->invoice->id,
                'company_id' => $this->invoice->company->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function addCustomerParty($root)
    {
        try {
            $customerParty = $this->dom->createElement('cac:AccountingCustomerParty');
            $this->addNode($customerParty, 'cbc:AdditionalAccountID', $this->invoice->customer->typeOrganization->code);

            $party = $this->dom->createElement('cac:Party');
            
            // Dirección física
            $physicalLocation = $this->dom->createElement('cac:PhysicalLocation');
            $address = $this->dom->createElement('cac:Address');
            $this->addNode($address, 'cbc:ID', $this->invoice->customer->location->code);
            $this->addNode($address, 'cbc:CityName', $this->invoice->customer->location->name);
            $this->addNode($address, 'cbc:CountrySubentity', $this->invoice->customer->location->department->name);
            $this->addNode($address, 'cbc:CountrySubentityCode', $this->invoice->customer->location->department->code);
            $this->addNode($address, 'cac:AddressLine/cbc:Line', $this->invoice->customer->address);
            $physicalLocation->appendChild($address);
            $party->appendChild($physicalLocation);

            // Información legal y tributaria
            $partyTaxScheme = $this->dom->createElement('cac:PartyTaxScheme');
            $this->addNode($partyTaxScheme, 'cbc:RegistrationName', $this->invoice->customer->business_name);
            
            // Identificación del cliente
            $companyId = $this->dom->createElement('cbc:CompanyID');
            $companyId->nodeValue = $this->invoice->customer->document_number;
            $companyId->setAttribute('schemeID', $this->invoice->customer->dv);
            $companyId->setAttribute('schemeName', $this->invoice->customer->typeDocument->code);
            $companyId->setAttribute('schemeAgencyID', '195');
            $companyId->setAttribute('schemeAgencyName', 'CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)');
            $partyTaxScheme->appendChild($companyId);

            // Régimen fiscal
            $taxScheme = $this->dom->createElement('cac:TaxScheme');
            $this->addNode($taxScheme, 'cbc:ID', $this->invoice->customer->typeRegime->code);
            $this->addNode($taxScheme, 'cbc:Name', $this->invoice->customer->typeRegime->name);
            $partyTaxScheme->appendChild($taxScheme);
            
            $party->appendChild($partyTaxScheme);

            // Información de contacto
            $contact = $this->dom->createElement('cac:Contact');
            $this->addNode($contact, 'cbc:Telephone', $this->invoice->customer->phone);
            $this->addNode($contact, 'cbc:ElectronicMail', $this->invoice->customer->email);
            $party->appendChild($contact);

            $customerParty->appendChild($party);
            $root->appendChild($customerParty);
        } catch (\Exception $e) {
            \Log::error('Error adding customer party: ' . $e->getMessage(), [
                'invoice_id' => $this->invoice->id,
                'customer_id' => $this->invoice->customer->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function getTaxRegimeName($regime)
    {
        $regimes = [
            '48' => 'Responsable de IVA',
            '49' => 'No Responsable de IVA',
            // Agregar más regímenes según sea necesario
        ];
        return $regimes[$regime] ?? '';
    }

    private function addTaxTotals(DOMElement $root): void
    {
        $taxTotals = $this->getTaxSubtotals();
        
        foreach ($taxTotals as $tax) {
            $taxTotal = $this->dom->createElement('cac:TaxTotal');
            
            $this->addNode($taxTotal, 'cbc:TaxAmount', number_format($tax['tax_amount'], 2, '.', ''), [
                'currencyID' => $this->invoice->currency->code
            ]);
            
            $taxSubtotal = $this->dom->createElement('cac:TaxSubtotal');
            
            $this->addNode($taxSubtotal, 'cbc:TaxableAmount', number_format($tax['taxable_amount'], 2, '.', ''), [
                'currencyID' => $this->invoice->currency->code
            ]);
            
            $this->addNode($taxSubtotal, 'cbc:TaxAmount', number_format($tax['tax_amount'], 2, '.', ''), [
                'currencyID' => $this->invoice->currency->code
            ]);
            
            $this->addNode($taxSubtotal, 'cbc:Percent', number_format($tax['rate'], 2, '.', ''));
            
            $taxCategory = $this->dom->createElement('cac:TaxCategory');
            $taxScheme = $this->dom->createElement('cac:TaxScheme');
            
            $this->addNode($taxScheme, 'cbc:ID', '01'); // IVA
            $this->addNode($taxScheme, 'cbc:Name', 'IVA');
            
            $taxCategory->appendChild($taxScheme);
            $taxSubtotal->appendChild($taxCategory);
            $taxTotal->appendChild($taxSubtotal);
            $root->appendChild($taxTotal);
        }
    }

    private function addLegalMonetaryTotal(DOMElement $root): void
    {
        $monetaryTotal = $this->dom->createElement('cac:LegalMonetaryTotal');

        // Valor Bruto
        $this->addNode($monetaryTotal, 'cbc:LineExtensionAmount', number_format($this->invoice->subtotal, 2, '.', ''), [
            'currencyID' => $this->invoice->currency->code
        ]);

        // Valor Bruto + Cargos - Descuentos
        $this->addNode($monetaryTotal, 'cbc:TaxExclusiveAmount', number_format($this->invoice->subtotal - $this->invoice->total_discount, 2, '.', ''), [
            'currencyID' => $this->invoice->currency->code
        ]);

        // Valor Bruto + Cargos - Descuentos + Impuestos
        $this->addNode($monetaryTotal, 'cbc:TaxInclusiveAmount', number_format($this->invoice->total_amount, 2, '.', ''), [
            'currencyID' => $this->invoice->currency->code
        ]);

        // Total Descuentos
        if ($this->invoice->total_discount > 0) {
            $this->addNode($monetaryTotal, 'cbc:AllowanceTotalAmount', number_format($this->invoice->total_discount, 2, '.', ''), [
                'currencyID' => $this->invoice->currency->code
            ]);
        }

        // Total Cargos
        if ($this->invoice->total_charges > 0) {
            $this->addNode($monetaryTotal, 'cbc:ChargeTotalAmount', number_format($this->invoice->total_charges, 2, '.', ''), [
                'currencyID' => $this->invoice->currency->code
            ]);
        }

        // Total a Pagar
        $this->addNode($monetaryTotal, 'cbc:PayableAmount', number_format($this->invoice->total_amount, 2, '.', ''), [
            'currencyID' => $this->invoice->currency->code
        ]);

        $root->appendChild($monetaryTotal);
    }

    private function addInvoiceLines(DOMElement $root): void
    {
        foreach ($this->invoice->lines as $index => $line) {
            $invoiceLine = $this->dom->createElement('cac:InvoiceLine');
            
            $this->addNode($invoiceLine, 'cbc:ID', $index + 1);
            
            if ($line->unitMeasure) {
                $this->addNode($invoiceLine, 'cbc:InvoicedQuantity', number_format($line->quantity, 2, '.', ''), [
                    'unitCode' => $line->unitMeasure->code
                ]);
            } else {
                $this->addNode($invoiceLine, 'cbc:InvoicedQuantity', number_format($line->quantity, 2, '.', ''));
            }
            
            $this->addNode($invoiceLine, 'cbc:LineExtensionAmount', number_format($line->subtotal, 2, '.', ''), [
                'currencyID' => $this->invoice->currency->code
            ]);
            
            // Información del item
            $item = $this->dom->createElement('cac:Item');
            $this->addNode($item, 'cbc:Description', $line->description);
            
            if ($line->product) {
                $standardItemIdentification = $this->dom->createElement('cac:StandardItemIdentification');
                $this->addNode($standardItemIdentification, 'cbc:ID', $line->product->code, [
                    'schemeID' => '999', // Estándar de código de producto
                    'schemeName' => 'Estándar de adopción del contribuyente'
                ]);
                
                $item->appendChild($standardItemIdentification);
            }
            
            $invoiceLine->appendChild($item);
            
            // Precio unitario
            $price = $this->dom->createElement('cac:Price');
            $this->addNode($price, 'cbc:PriceAmount', number_format($line->price, 2, '.', ''), [
                'currencyID' => $this->invoice->currency->code
            ]);
            
            $invoiceLine->appendChild($price);
            $root->appendChild($invoiceLine);
        }
    }

    private function getTaxSubtotals(): array
    {
        // Agrupar impuestos por tipo
        $taxes = [];
        foreach ($this->invoice->lines as $line) {
            if (!$line->tax) {
                continue;
            }
            
            $taxableAmount = $line->subtotal - $line->discount_amount;
            $key = $line->tax->id;
            
            if (!isset($taxes[$key])) {
                $taxes[$key] = [
                    'id' => $line->tax->id,
                    'name' => $line->tax->name,
                    'rate' => $line->tax->rate,
                    'taxable_amount' => 0,
                    'tax_amount' => 0
                ];
            }
            
            $taxes[$key]['taxable_amount'] += $taxableAmount;
            $taxes[$key]['tax_amount'] += $line->tax_amount;
        }
        
        return array_values($taxes);
    }

    private function addNode(DOMElement $parent, string $name, ?string $value, array $attributes = []): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }
        
        try {
            // Si el nombre está vacío, no podemos crear el elemento
            if (empty(trim($name))) {
                throw new \InvalidArgumentException('Element name cannot be empty');
            }
            
            // Limpiar caracteres no válidos en XML
            $value = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $value);
            
            // Si el nombre contiene /, es un elemento anidado
            if (strpos($name, '/') !== false) {
                $parts = explode('/', $name);
                $currentParent = $parent;
                
                for ($i = 0; $i < count($parts) - 1; $i++) {
                    if (empty(trim($parts[$i]))) {
                        throw new \InvalidArgumentException('Element name cannot be empty in path: ' . $name);
                    }
                    $element = $this->dom->createElement($parts[$i]);
                    $currentParent->appendChild($element);
                    $currentParent = $element;
                }
                
                $name = end($parts);
                if (empty(trim($name))) {
                    throw new \InvalidArgumentException('Element name cannot be empty in path: ' . $name);
                }
            }
            
            // Crear el elemento
            $node = $this->dom->createElement($name);
            $textNode = $this->dom->createTextNode($value);
            $node->appendChild($textNode);
            
            // Agregar atributos
            foreach ($attributes as $attr => $attrValue) {
                if ($attrValue !== null && trim($attrValue) !== '') {
                    $attrValue = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $attrValue);
                    $node->setAttribute($attr, $attrValue);
                }
            }
            
            $parent->appendChild($node);
        } catch (\Exception $e) {
            \Log::error('Error adding XML node: ' . $e->getMessage(), [
                'name' => $name,
                'value' => $value,
                'attributes' => $attributes,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
