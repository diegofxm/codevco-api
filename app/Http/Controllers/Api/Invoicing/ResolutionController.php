<?php

namespace App\Http\Controllers\Api\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\Invoicing\Resolution;
use App\Models\Companies\Company;
use App\Models\Companies\Branch;
use App\Models\Catalogs\TypeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ResolutionController extends Controller
{
    protected function transformResolution($resolution)
    {
        return [
            'id' => $resolution->id,
            'company' => [
                'id' => $resolution->company->id,
                'name' => $resolution->company->name
            ],
            'branch' => [
                'id' => $resolution->branch->id,
                'name' => $resolution->branch->name,
                'code' => $resolution->branch->code
            ],
            'type_document' => [
                'id' => $resolution->typeDocument->id,
                'name' => $resolution->typeDocument->name,
                'code' => $resolution->typeDocument->code
            ],
            'prefix' => $resolution->prefix,
            'resolution' => $resolution->resolution,
            'resolution_date' => $resolution->resolution_date->format('Y-m-d'),
            'expiration_date' => $resolution->expiration_date->format('Y-m-d'),
            'technical_key' => $resolution->technical_key,
            'from' => $resolution->from,
            'to' => $resolution->to,
            'current_number' => $resolution->current_number,
            'status' => $resolution->status,
            'created_at' => $resolution->created_at,
            'updated_at' => $resolution->updated_at
        ];
    }

    public function index(Request $request)
    {
        try {
            $query = Resolution::with(['company', 'branch', 'typeDocument']);

            // Filtrar por compañía si se proporciona
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            // Filtrar por sucursal si se proporciona
            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filtrar por tipo de documento si se proporciona
            if ($request->has('type_document_id')) {
                $query->where('type_document_id', $request->type_document_id);
            }

            // Filtrar por estado si se proporciona
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $resolutions = $query->get();

            return response()->json([
                'message' => 'Resolutions retrieved successfully',
                'resolutions' => $resolutions->map(function ($resolution) {
                    return $this->transformResolution($resolution);
                })
            ]);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'message' => 'Error retrieving resolutions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'branch_id' => [
                    'required',
                    'exists:branches,id',
                    Rule::unique('resolutions')->where(function ($query) use ($request) {
                        return $query->where('company_id', $request->company_id)
                                   ->where('branch_id', $request->branch_id)
                                   ->where('type_document_id', $request->type_document_id)
                                   ->where('prefix', $request->prefix)
                                   ->whereNull('deleted_at');
                    })
                ],
                'type_document_id' => 'required|exists:type_documents,id',
                'prefix' => 'required|string|max:4',
                'resolution' => 'required|string',
                'resolution_date' => 'required|date',
                'expiration_date' => 'required|date|after:resolution_date',
                'technical_key' => 'required|string',
                'from' => 'required|integer|min:1',
                'to' => 'required|integer|gt:from',
                'current_number' => 'required|integer|gte:from|lte:to',
                'status' => 'boolean'
            ]);

            DB::beginTransaction();

            $resolution = Resolution::create($validated);

            DB::commit();

            $resolution->load(['company', 'branch', 'typeDocument']);

            return response()->json([
                'message' => 'Resolution created successfully',
                'resolution' => $this->transformResolution($resolution)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'message' => 'Error creating resolution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $resolution = Resolution::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $resolution
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resolution not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving resolution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $resolution = Resolution::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'company_id' => 'exists:companies,id',
                'branch_id' => 'exists:branches,id',
                'type_document_id' => 'exists:type_documents,id',
                'prefix' => 'string|max:4',
                'resolution' => 'string|max:20',
                'resolution_date' => 'date',
                'expiration_date' => 'date|after:resolution_date',
                'technical_key' => 'string|max:100',
                'from' => 'integer|min:1',
                'to' => 'integer|gt:from',
                'current_number' => 'integer|min:1',
                'status' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $resolution->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Resolution updated successfully',
                'data' => $resolution
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resolution not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating resolution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $resolution = Resolution::findOrFail($id);
            
            // Verificar si la resolución está siendo usada
            $hasInvoices = Invoice::where('resolution_id', $id)->exists();
            $hasCreditNotes = CreditNote::where('resolution_id', $id)->exists();
            $hasDebitNotes = DebitNote::where('resolution_id', $id)->exists();

            if ($hasInvoices || $hasCreditNotes || $hasDebitNotes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resolution cannot be deleted because it is being used'
                ], 422);
            }

            $resolution->delete();

            return response()->json([
                'success' => true,
                'message' => 'Resolution deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resolution not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting resolution',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
