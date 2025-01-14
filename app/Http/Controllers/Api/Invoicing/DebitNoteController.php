<?php

namespace App\Http\Controllers\Api\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\Invoicing\DebitNote;
use App\Models\Invoicing\Invoice;
use App\Models\Invoicing\Resolution;
use App\Services\DebitNotePdfService;
use App\Services\Xml\DebitNoteXmlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DebitNoteController extends Controller
{
    public function index()
    {
        try {
            $debitNotes = DebitNote::with([
                'invoice:id,number,prefix',
                'resolution:id',
                'branch:id,name',
            ])->get();

            return response()->json([
                'success' => true,
                'data' => $debitNotes
            ]);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas débito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|exists:invoices,id',
                'resolution_id' => 'required|exists:resolutions,id',
                'branch_id' => 'required|exists:branches,id',
                'issue_date' => 'required|date',
                'notes' => 'nullable|string',
                'correction_concept' => 'required|string',
                'discrepancy_code' => 'required|in:1,2,3,4',
                'lines' => 'required|array',
                'lines.*.invoice_line_id' => 'required|exists:invoice_lines,id',
                'lines.*.quantity' => 'required|numeric|min:0',
                'lines.*.price' => 'required|numeric|min:0',
                'lines.*.discount_rate' => 'required|numeric|min:0|max:100',
                'lines.*.tax_id' => 'required|exists:taxes,id',
                'lines.*.unit_measure_id' => 'required|exists:unit_measures,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Validar que la factura esté emitida
            $invoice = Invoice::find($request->invoice_id);
            if ($invoice->status !== 'issued') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'La factura debe estar emitida para crear una nota débito'
                ], 422);
            }

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

            // Inicializar totales
            $totalDiscount = 0;
            $totalTax = 0;
            $subtotal = 0;

            // Calcular totales antes de generar el CUFE
            foreach ($request->lines as $line) {
                $discountAmount = ($line['price'] * $line['quantity']) * ($line['discount_rate'] / 100);
                $lineSubtotal = ($line['price'] * $line['quantity']) - $discountAmount;
                $tax = DB::table('taxes')->find($line['tax_id']);
                $taxAmount = $lineSubtotal * ($tax->rate / 100);

                $totalDiscount += $discountAmount;
                $totalTax += $taxAmount;
                $subtotal += $lineSubtotal;
            }

            // Generar CUFE según especificación DIAN
            $cufeData = [
                'invoice_number' => $nextNumber,
                'issue_date' => $request->issue_date,
                'invoice_amount' => $subtotal + $totalTax,
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'document_type' => '92' // Tipo de documento para nota débito
            ];

            $cufe = $this->generateCUFE($cufeData);

            $debitNote = DebitNote::create([
                'invoice_id' => $request->invoice_id,
                'resolution_id' => $request->resolution_id,
                'branch_id' => $request->branch_id,
                'prefix' => $resolution->prefix,
                'number' => $nextNumber,
                'cufe' => $cufe,
                'issue_date' => $request->issue_date,
                'notes' => $request->notes,
                'correction_concept' => $request->correction_concept,
                'discrepancy_code' => $request->discrepancy_code,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'subtotal' => $subtotal,
                'total_amount' => $subtotal + $totalTax,
                'status' => 'draft'
            ]);

            foreach ($request->lines as $line) {
                $invoiceLine = DB::table('invoice_lines')->find($line['invoice_line_id']);

                // Validar que la cantidad no exceda la disponible
                $usedQuantity = DB::table('invoice_lines')
                    ->where('id', $line['invoice_line_id'])
                    ->whereNotNull('debit_note_id')
                    ->sum('quantity');

                $availableQuantity = $invoiceLine->quantity - $usedQuantity;

                if ($line['quantity'] > $availableQuantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'La cantidad excede la disponible en la línea de la factura',
                        'errors' => [
                            'lines' => [
                                'La cantidad ' . $line['quantity'] . ' excede la cantidad disponible ' . $availableQuantity
                            ]
                        ]
                    ], 422);
                }

                $tax = DB::table('taxes')->find($line['tax_id']);

                $discountAmount = ($line['price'] * $line['quantity']) * ($line['discount_rate'] / 100);
                $lineSubtotal = ($line['price'] * $line['quantity']) - $discountAmount;
                $taxAmount = $lineSubtotal * ($tax->rate / 100);

                DB::table('invoice_lines')->insert([
                    'invoice_id' => $request->invoice_id,
                    'debit_note_id' => $debitNote->id,
                    'product_id' => $invoiceLine->product_id,
                    'description' => $invoiceLine->description,
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'discount_rate' => $line['discount_rate'],
                    'discount_amount' => $discountAmount,
                    'unit_measure_id' => $line['unit_measure_id'],
                    'tax_id' => $line['tax_id'],
                    'tax_amount' => $taxAmount,
                    'subtotal' => $lineSubtotal,
                    'total' => $lineSubtotal + $taxAmount,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            // Cargar las relaciones
            $debitNote->load([
                'invoice:id,number,prefix',
                'resolution:id',
                'branch:id,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota débito creada exitosamente',
                'data' => $debitNote
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la nota débito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $debitNote = DebitNote::with([
                'invoice:id,number,prefix',
                'resolution:id',
                'branch:id,name'
            ])->find($id);

            if (!$debitNote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota débito no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $debitNote
            ]);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la nota débito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $debitNote = DebitNote::find($id);

            if (!$debitNote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota débito no encontrada'
                ], 404);
            }

            // Validar campos que se pueden actualizar
            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string',
                'correction_concept' => 'string',
                'discrepancy_code' => 'string',
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
            $debitNote->fill($request->only([
                'notes',
                'correction_concept',
                'discrepancy_code',
                'status'
            ]));

            $debitNote->save();

            DB::commit();

            // Cargar las relaciones
            $debitNote->load([
                'invoice:id,number,prefix',
                'resolution:id',
                'branch:id,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota débito actualizada exitosamente',
                'data' => $debitNote
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota débito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $debitNote = DebitNote::find($id);

            if (!$debitNote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota débito no encontrada'
                ], 404);
            }

            if ($debitNote->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar notas débito en estado borrador'
                ], 422);
            }

            DB::beginTransaction();

            $debitNote->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nota débito eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota débito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generatePdf($id)
    {
        try {
            $debitNote = DebitNote::with([
                'invoice.company.typeOrganization',
                'invoice.company.typeDocument',
                'invoice.company.typeRegime',
                'invoice.company.location.department',
                'invoice.customer.typeOrganization',
                'invoice.customer.typeDocument',
                'invoice.customer.typeRegime',
                'invoice.customer.location.department',
                'lines'
            ])->find($id);

            if (!$debitNote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debit note not found'
                ], 404);
            }
            
            $pdfService = new DebitNotePdfService($debitNote);
            return response()->json($pdfService->generatePdf());
        } catch (\Exception $e) {
            logger('Error generating PDF: ' . $e->getMessage(), [
                'debit_note_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateXml($id)
    {
        try {
            $debitNote = DebitNote::with([
                'invoice.company.typeOrganization',
                'invoice.company.typeDocument',
                'invoice.company.typeRegime',
                'invoice.company.location.department',
                'invoice.customer.typeOrganization',
                'invoice.customer.typeDocument',
                'invoice.customer.typeRegime',
                'invoice.customer.location.department',
                'lines'
            ])->find($id);

            if (!$debitNote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debit note not found'
                ], 404);
            }

            $xmlService = new DebitNoteXmlService($debitNote);
            return response()->json($xmlService->generate());
        } catch (\Exception $e) {
            logger()->error('Error generating XML: ' . $e->getMessage(), [
                'debit_note_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error generating XML',
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

            $debitNote = DebitNote::findOrFail($id);
            $newStatus = $request->status;

            // Validar transiciones de estado permitidas
            $allowedTransitions = [
                'draft' => ['issued'],
                'issued' => ['voided'],
                'voided' => []
            ];

            if (!in_array($newStatus, $allowedTransitions[$debitNote->status] ?? [])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status transition',
                    'errors' => ['status' => ['Cannot change from ' . $debitNote->status . ' to ' . $newStatus]]
                ], 422);
            }

            $debitNote->status = $newStatus;
            $debitNote->save();

            return response()->json([
                'success' => true,
                'message' => 'Status changed successfully',
                'data' => $debitNote
            ]);

        } catch (\Exception $e) {
            logger()->error('Error changing debit note status: ' . $e->getMessage(), [
                'debit_note_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error changing status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function pdf(DebitNote $debitNote)
    {
        try {
            $result = \App\Services\Pdf\DebitNotePdfService::make($debitNote)->generate();
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            logger('Error generating PDF: ' . $e->getMessage(), [
                'debit_note_id' => $debitNote->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateCUFE($data)
    {
        $numFac = str_pad($data['invoice_number'], 8, '0', STR_PAD_LEFT);
        $fecFac = date('Y-m-d', strtotime($data['issue_date']));
        $valFac = number_format($data['invoice_amount'], 2, '.', '');
        $nitOFE = str_pad($data['company_id'], 10, '0', STR_PAD_LEFT);
        $nitADQ = str_pad($data['customer_id'], 10, '0', STR_PAD_LEFT);
        $codImp = '01'; // IVA
        $valImp = '19.00';
        $codImp2 = '04'; // Impuesto al consumo
        $valImp2 = '0.00';
        $codImp3 = '03'; // ICA
        $valImp3 = '0.00';
        $valTot = $valFac;
        $tipoAmb = '1'; // Producción: 1, Pruebas: 2
        $clave = 'A1B2C3'; // Clave técnica del software

        // Concatenar los valores
        $string = $numFac . $fecFac . $valFac . $codImp . $valImp . $codImp2 . $valImp2 . 
                 $codImp3 . $valImp3 . $valTot . $nitOFE . $nitADQ . $clave . $tipoAmb;

        // Generar el CUFE usando SHA384
        return hash('sha384', $string);
    }
}
