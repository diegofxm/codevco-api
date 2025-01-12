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
                'success' => true,
                'data' => $transformedInvoices
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las facturas',
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
                    'message' => 'Error de validación',
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
                    'message' => 'La resolución no está activa'
                ], 422);
            }

            if ($resolution->current_number > $resolution->to) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'La resolución ha alcanzado su número máximo'
                ], 422);
            }

            if ($resolution->expiration_date < now()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'La resolución ha expirado'
                ], 422);
            }

            // Obtener el siguiente número
            $nextNumber = $resolution->current_number;

            // Actualizar el número actual de la resolución
            $resolution->current_number = $nextNumber + 1;
            $resolution->save();

            // Generar CUFE único
            $cufe = Str::uuid()->toString();

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
                'total_discount' => 0,
                'total_tax' => 0,
                'subtotal' => 0,
                'total_amount' => 0,
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
                            'message' => 'Error de validación',
                            'errors' => [
                                'lines' => ["El producto con ID {$line} no existe"]
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
                            'message' => 'Error de validación en producto nuevo',
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
                            'message' => 'Error de validación en línea de factura',
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
                'message' => 'Factura creada exitosamente',
                'data' => $this->transformInvoice($invoice)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la factura',
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
                    'message' => 'Factura no encontrada'
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
                'message' => 'Error al obtener la factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $invoice = Invoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada'
                ], 404);
            }

            // Validar campos que se pueden actualizar
            $validator = Validator::make($request->all(), [
                'payment_due_date' => 'date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'status' => 'in:draft,approved,voided'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar solo los campos permitidos
            $invoice->fill($request->only([
                'payment_due_date',
                'notes',
                'status'
            ]));

            $invoice->save();

            DB::commit();

            // Cargar las relaciones
            $invoice->load([
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
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Factura actualizada exitosamente',
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $invoice = Invoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada'
                ], 404);
            }

            if ($invoice->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar facturas en estado borrador'
                ], 422);
            }

            DB::beginTransaction();

            $invoice->lines()->delete();
            $invoice->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Factura eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}