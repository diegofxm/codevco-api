<?php

namespace App\Http\Controllers\Api\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\Invoicing\Invoice;
use App\Models\Invoicing\InvoiceLine;
use App\Models\Invoicing\Resolution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    private function transformInvoice($invoice)
    {
        return [
            'id' => $invoice->id,
            'company' => [
                'id' => $invoice->company->id,
                'business_name' => $invoice->company->business_name
            ],
            'customer' => [
                'id' => $invoice->customer->id,
                'business_name' => $invoice->customer->business_name,
                'document_number' => $invoice->customer->document_number
            ],
            'resolution' => [
                'id' => $invoice->resolution->id,
                'number' => $invoice->resolution->number
            ],
            'branch' => [
                'id' => $invoice->branch->id,
                'name' => $invoice->branch->name
            ],
            'currency' => [
                'id' => $invoice->currency->id,
                'code' => $invoice->currency->code,
                'name' => $invoice->currency->name
            ],
            'payment_method' => [
                'id' => $invoice->paymentMethod->id,
                'name' => $invoice->paymentMethod->name
            ],
            'type_operation' => [
                'id' => $invoice->typeOperation->id,
                'name' => $invoice->typeOperation->name
            ],
            'number' => $invoice->number,
            'prefix' => $invoice->prefix,
            'cufe' => $invoice->cufe,
            'issue_date' => $invoice->issue_date,
            'payment_due_date' => $invoice->payment_due_date,
            'notes' => $invoice->notes,
            'payment_exchange_rate' => $invoice->payment_exchange_rate,
            'total_discount' => $invoice->total_discount,
            'total_tax' => $invoice->total_tax,
            'subtotal' => $invoice->subtotal,
            'total_amount' => $invoice->total_amount,
            'status' => $invoice->status,
            'lines' => $invoice->lines->map(function ($line) {
                return [
                    'id' => $line->id,
                    'product' => [
                        'id' => $line->product->id,
                        'name' => $line->product->name,
                        'code' => $line->product->code
                    ],
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'price' => $line->price,
                    'discount_rate' => $line->discount_rate,
                    'discount_amount' => $line->discount_amount,
                    'unit_measure' => [
                        'id' => $line->unitMeasure->id,
                        'name' => $line->unitMeasure->name,
                        'code' => $line->unitMeasure->code
                    ],
                    'tax' => [
                        'id' => $line->tax->id,
                        'name' => $line->tax->name,
                        'rate' => $line->tax->rate
                    ],
                    'tax_amount' => $line->tax_amount,
                    'subtotal' => $line->subtotal,
                    'total' => $line->total,
                    'period_start_date' => $line->period_start_date,
                    'period_end_date' => $line->period_end_date
                ];
            }),
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at
        ];
    }

    public function index()
    {
        try {
            $invoices = Invoice::with([
                'company',
                'customer',
                'resolution',
                'branch',
                'currency',
                'paymentMethod',
                'typeOperation',
                'lines.product',
                'lines.unitMeasure',
                'lines.tax'
            ])->get();

            $transformedInvoices = $invoices->map(function ($invoice) {
                return $this->transformInvoice($invoice);
            });

            return response()->json([
                'message' => 'Invoices retrieved successfully',
                'data' => $transformedInvoices
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'customer_id' => 'required|exists:customers,id',
                'resolution_id' => 'required|exists:resolutions,id',
                'branch_id' => 'required|exists:branches,id',
                'currency_id' => 'required|exists:currencies,id',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'type_operation_id' => 'required|exists:type_operations,id',
                'issue_date' => 'required|date',
                'payment_due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'payment_exchange_rate' => 'required|numeric|min:0',
                'lines' => 'required|array|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Obtener la resolución y validar
            $resolution = Resolution::find($request->resolution_id);

            if (!$resolution->status) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Resolution is not active'
                ], 422);
            }

            if ($resolution->current_number > $resolution->to) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'The resolution has reached its maximum number'
                ], 422);
            }

            if ($resolution->expiration_date < now()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'The resolution has expired'
                ], 422);
            }

            // Obtener el siguiente número
            $nextNumber = $resolution->current_number === null ? $resolution->from : $resolution->current_number;

            // Actualizar el número actual de la resolución
            $resolution->current_number = $nextNumber + 1;
            $resolution->save();

            // Calcular totales primero
            $subtotal = 0;
            $totalDiscount = 0;
            $totalTax = 0;

            foreach ($request->lines as $line) {
                // Validar línea
                $lineValidator = Validator::make($line, [
                    'quantity' => 'required|numeric|min:0',
                    'price' => 'required|numeric|min:0',
                    'discount_rate' => 'nullable|numeric|min:0|max:100',
                    'tax_id' => 'required|exists:taxes,id'
                ]);

                if ($lineValidator->fails()) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Line validation error',
                        'errors' => $lineValidator->errors()
                    ], 422);
                }

                $quantity = $line['quantity'];
                $price = $line['price'];
                $discountRate = $line['discount_rate'] ?? 0;

                $lineSubtotal = $quantity * $price;
                $lineDiscount = $lineSubtotal * ($discountRate / 100);
                
                // Obtener la tasa de IVA
                $tax = DB::table('taxes')->find($line['tax_id']);
                if (!$tax) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Tax not found'
                    ], 422);
                }
                
                $lineTax = ($lineSubtotal - $lineDiscount) * ($tax->rate / 100);

                $subtotal += $lineSubtotal;
                $totalDiscount += $lineDiscount;
                $totalTax += $lineTax;
            }

            $totalAmount = $subtotal - $totalDiscount + $totalTax;

            // Generar CUFE según especificación DIAN
            $cufeData = [
                'invoice_number' => $nextNumber,
                'issue_date' => $request->issue_date,
                'issue_time' => now()->format('H:i:s'),
                'invoice_value' => $totalAmount,
                'vat_value' => $totalTax,
                'document_type' => '01', // Factura electrónica
                'document_number' => $request->customer_id,
                'technical_key' => $resolution->technical_key ?? config('invoicing.default_technical_key'),
                'payment_type' => '1' // Contado
            ];
            
            try {
                $cufe = $this->generateCUFE($cufeData);
                
                if (!$this->validateCUFE($cufe)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Error generating valid CUFE'
                    ], 500);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error generating CUFE',
                    'error' => $e->getMessage()
                ], 500);
            }

            $invoice = Invoice::create([
                'company_id' => $request->company_id,
                'customer_id' => $request->customer_id,
                'resolution_id' => $request->resolution_id,
                'branch_id' => $request->branch_id,
                'currency_id' => $request->currency_id,
                'payment_method_id' => $request->payment_method_id,
                'type_operation_id' => $request->type_operation_id,
                'prefix' => $resolution->prefix,
                'number' => $nextNumber,
                'cufe' => $cufe,
                'issue_date' => $request->issue_date,
                'payment_due_date' => $request->payment_due_date,
                'notes' => $request->notes,
                'payment_exchange_rate' => $request->payment_exchange_rate,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
                'status' => 'draft'
            ]);

            $subtotal = 0;
            $totalDiscount = 0;
            $totalTax = 0;

            foreach ($request->lines as $line) {
                if (is_numeric($line)) {
                    // Si es un ID, buscar el producto
                    $product = DB::table('products')->find($line);

                    if (!$product) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Validation error',
                            'errors' => [
                                'lines' => ["Product with ID {$line} does not exist"]
                            ]
                        ], 422);
                    }

                    $productData = $product;
                } elseif (is_array($line) && isset($line['code'])) {
                    // Si es un array con datos de producto nuevo
                    $validator = Validator::make($line, [
                        'code' => 'required|string|unique:products,code',
                        'name' => 'required|string',
                        'description' => 'required|string',
                        'brand' => 'nullable|string',
                        'model' => 'nullable|string',
                        'customs_tariff' => 'nullable|string',
                        'price' => 'required|numeric|min:0',
                        'unit_measure_id' => 'required|exists:unit_measures,id',
                        'tax_id' => 'required|exists:taxes,id',
                        'status' => 'required|boolean'
                    ]);

                    if ($validator->fails()) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Validation error on new product',
                            'errors' => $validator->errors()
                        ], 422);
                    }

                    // Crear el nuevo producto
                    $product = DB::table('products')->insertGetId([
                        'company_id' => $request->company_id,
                        'code' => $line['code'],
                        'name' => $line['name'],
                        'description' => $line['description'],
                        'brand' => $line['brand'] ?? null,
                        'model' => $line['model'] ?? null,
                        'customs_tariff' => $line['customs_tariff'] ?? null,
                        'price' => $line['price'],
                        'unit_measure_id' => $line['unit_measure_id'],
                        'tax_id' => $line['tax_id'],
                        'status' => $line['status'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $productData = (object)[
                        'id' => $product,
                        'name' => $line['name'],
                        'price' => $line['price'],
                        'unit_measure_id' => $line['unit_measure_id'],
                        'tax_id' => $line['tax_id']
                    ];
                } else {
                    // Si es un array con datos de línea
                    $validator = Validator::make($line, [
                        'product_id' => 'required|exists:products,id',
                        'description' => 'required|string',
                        'quantity' => 'required|numeric|min:0',
                        'price' => 'required|numeric|min:0',
                        'discount_rate' => 'nullable|numeric|min:0|max:100',
                        'unit_measure_id' => 'required|exists:unit_measures,id',
                        'tax_id' => 'required|exists:taxes,id',
                        'period_start_date' => 'nullable|date',
                        'period_end_date' => 'nullable|date|after_or_equal:period_start_date'
                    ]);

                    if ($validator->fails()) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Invoice line validation error',
                            'errors' => $validator->errors()
                        ], 422);
                    }

                    $productData = DB::table('products')->find($line['product_id']);
                }

                // Obtener el impuesto
                $tax = DB::table('taxes')->find($productData->tax_id);
                $taxRate = $tax ? $tax->rate : 0;

                // Establecer valores por defecto o usar los proporcionados
                $quantity = isset($line['quantity']) ? $line['quantity'] : 1;
                $price = isset($line['price']) ? $line['price'] : $productData->price;
                $discountRate = isset($line['discount_rate']) ? $line['discount_rate'] : 0;

                // Calcular los montos
                $lineSubtotal = $quantity * $price;
                $discountAmount = ($lineSubtotal * $discountRate / 100);
                $lineSubtotalAfterDiscount = $lineSubtotal - $discountAmount;
                $lineTaxAmount = $lineSubtotalAfterDiscount * ($taxRate / 100);

                // Crear la línea de factura
                $invoiceLine = new InvoiceLine([
                    'product_id' => $productData->id,
                    'description' => isset($line['description']) ? $line['description'] : $productData->name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount_rate' => $discountRate,
                    'discount_amount' => $discountAmount,
                    'unit_measure_id' => isset($line['unit_measure_id']) ? $line['unit_measure_id'] : $productData->unit_measure_id,
                    'tax_id' => isset($line['tax_id']) ? $line['tax_id'] : $productData->tax_id,
                    'tax_amount' => $lineTaxAmount,
                    'subtotal' => $lineSubtotalAfterDiscount,
                    'total' => $lineSubtotalAfterDiscount + $lineTaxAmount,
                    'period_start_date' => $line['period_start_date'] ?? null,
                    'period_end_date' => $line['period_end_date'] ?? null
                ]);

                $invoice->lines()->save($invoiceLine);

                $subtotal += $lineSubtotal;
                $totalDiscount += $discountAmount;
                $totalTax += $lineTaxAmount;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'total_amount' => $subtotal - $totalDiscount + $totalTax
            ]);

            DB::commit();

            $invoice->load([
                'company',
                'customer',
                'resolution',
                'branch',
                'currency',
                'paymentMethod',
                'typeOperation',
                'lines.product',
                'lines.unitMeasure',
                'lines.tax'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $this->transformInvoice($invoice)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $invoice = Invoice::with([
                'company:id,business_name',
                'customer:id,business_name,document_number',
                'resolution:id',
                'branch:id,name',
                'currency:id,code,name',
                'paymentMethod:id,name',
                'typeOperation:id,name',
                'lines.product:id,name,code',
                'lines.unitMeasure:id,name,code',
                'lines.tax:id,name,rate'
            ])->find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if ($invoice->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft invoices can be updated'
                ], 422);
            }

            // Solo permitir actualizar campos no críticos
            $allowedFields = ['notes', 'payment_due_date'];
            $updateData = $request->only($allowedFields);

            $invoice->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice->load([
                    'company', 'customer', 'resolution', 'branch',
                    'currency', 'paymentMethod', 'typeOperation', 'lines'
                ])
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if ($invoice->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft invoices can be deleted'
                ], 422);
            }

            $invoice->delete();

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:draft,issued,voided'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invoice = Invoice::findOrFail($id);
            $newStatus = $request->status;

            // Validar transiciones de estado permitidas
            $allowedTransitions = [
                'draft' => ['issued'],
                'issued' => ['voided'],
                'voided' => []
            ];

            if (!in_array($newStatus, $allowedTransitions[$invoice->status])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot change status from {$invoice->status} to {$newStatus}"
                ], 422);
            }

            // Si se está emitiendo la factura, validar que tenga todos los campos requeridos
            if ($newStatus === 'issued') {
                // Aquí irían validaciones adicionales antes de emitir
                if ($invoice->lines->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot issue an invoice without lines'
                    ], 422);
                }
            }

            $invoice->status = $newStatus;
            $invoice->save();

            return response()->json([
                'success' => true,
                'message' => 'Invoice status updated successfully',
                'data' => $invoice->load([
                    'company', 'customer', 'resolution', 'branch',
                    'currency', 'paymentMethod', 'typeOperation', 'lines'
                ])
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error changing invoice status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateCUFE($data)
    {
        // Validar datos requeridos
        $requiredFields = [
            'invoice_number', 'issue_date', 'issue_time', 'invoice_value',
            'vat_value', 'document_type', 'document_number', 'technical_key'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Missing required field for CUFE: {$field}");
            }
        }

        // Formatear campos según DIAN
        $formattedData = [
            'NumFac' => str_pad($data['invoice_number'], 8, '0', STR_PAD_LEFT),
            'FecFac' => date('Y-m-d', strtotime($data['issue_date'])),
            'HorFac' => date('H:i:s', strtotime($data['issue_time'])) . '-05:00',
            'ValFac' => number_format($data['invoice_value'], 2, '.', ''),
            'CodImp' => '01', // IVA
            'ValImp' => number_format($data['vat_value'], 2, '.', ''),
            'DocAdq' => str_pad($data['document_number'], 10, '0', STR_PAD_LEFT),
            'TipoAmb' => '1', // 1: Producción, 2: Pruebas
            'ClTec' => $data['technical_key']
        ];

        // Construir cadena CUFE según orden DIAN
        $cufeString = sprintf(
            '%s%s%s%s%s%s%s%s%s',
            $formattedData['NumFac'],
            str_replace(['-', ':'], '', $formattedData['FecFac']),
            str_replace([':', '-'], '', substr($formattedData['HorFac'], 0, 8)),
            str_replace('.', '', number_format($formattedData['ValFac'], 2, '.', '')),
            $formattedData['CodImp'],
            str_replace('.', '', number_format($formattedData['ValImp'], 2, '.', '')),
            $formattedData['DocAdq'],
            $formattedData['ClTec'],
            $formattedData['TipoAmb']
        );

        // Aplicar algoritmo SHA-384 según DIAN
        $cufe = hash('sha384', $cufeString);

        // Guardar datos de control
        Log::info('CUFE Generation', [
            'input_data' => $data,
            'formatted_data' => $formattedData,
            'cufe_string' => $cufeString,
            'cufe' => $cufe
        ]);

        return $cufe;
    }

    private function validateCUFE($cufe)
    {
        // Validar longitud (SHA-384 produce 96 caracteres hexadecimales)
        if (strlen($cufe) !== 96) {
            return false;
        }

        // Validar que solo contiene caracteres hexadecimales
        if (!ctype_xdigit($cufe)) {
            return false;
        }

        return true;
    }
}
