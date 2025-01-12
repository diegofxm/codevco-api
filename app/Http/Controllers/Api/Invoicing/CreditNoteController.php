<?php

namespace App\Http\Controllers\Api\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\Invoicing\CreditNote;
use App\Models\Invoicing\Resolution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreditNoteController extends Controller
{
    public function index()
    {
        try {
            $creditNotes = CreditNote::with([
                'invoice:id,number,prefix',
                'resolution:id',
                'branch:id,name',
            ])->get();

            return response()->json([
                'success' => true,
                'data' => $creditNotes
            ]);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas crédito',
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
                'discrepancy_code' => 'required|string'
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

            $creditNote = CreditNote::create([
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
                'total_discount' => 0,
                'total_tax' => 0,
                'subtotal' => 0,
                'total_amount' => 0,
                'status' => 'draft'
            ]);

            DB::commit();

            // Cargar las relaciones
            $creditNote->load([
                'invoice:id,number,prefix',
                'resolution:id',
                'branch:id,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota crédito creada exitosamente',
                'data' => $creditNote
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la nota crédito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $creditNote = CreditNote::with([
                'invoice:id,number,prefix',
                'resolution:id',
                'branch:id,name'
            ])->find($id);

            if (!$creditNote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota crédito no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $creditNote
            ]);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la nota crédito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $creditNote = CreditNote::find($id);

            if (!$creditNote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota crédito no encontrada'
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
            $creditNote->fill($request->only([
                'notes',
                'correction_concept',
                'discrepancy_code',
                'status'
            ]));

            $creditNote->save();

            DB::commit();

            // Cargar las relaciones
            $creditNote->load([
                'invoice:id,number,prefix',
                'resolution:id',
                'branch:id,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota crédito actualizada exitosamente',
                'data' => $creditNote
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota crédito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $creditNote = CreditNote::find($id);

            if (!$creditNote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota crédito no encontrada'
                ], 404);
            }

            if ($creditNote->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar notas crédito en estado borrador'
                ], 422);
            }

            DB::beginTransaction();

            $creditNote->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nota crédito eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota crédito',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
