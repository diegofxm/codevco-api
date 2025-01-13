<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Catalogs\TypeLiability;
use App\Models\Catalogs\EconomicActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    private function transformCustomer($customer)
    {
        // Verificar que las relaciones existan antes de acceder a ellas
        return [
            'id' => $customer->id,
            'company' => $customer->company ? [
                'id' => $customer->company->id,
                'business_name' => $customer->company->business_name
            ] : null,
            'type_organization' => $customer->typeOrganization ? [
                'id' => $customer->typeOrganization->id,
                'name' => $customer->typeOrganization->name,
                'code' => $customer->typeOrganization->code
            ] : null,
            'type_document' => $customer->typeDocument ? [
                'id' => $customer->typeDocument->id,
                'name' => $customer->typeDocument->name,
                'code' => $customer->typeDocument->code
            ] : null,
            'document_number' => $customer->document_number,
            'dv' => $customer->dv,
            'business_name' => $customer->business_name,
            'trade_name' => $customer->trade_name,
            'type_regime' => $customer->typeRegime ? [
                'id' => $customer->typeRegime->id,
                'name' => $customer->typeRegime->name,
                'code' => $customer->typeRegime->code
            ] : null,
            'type_liabilities' => $customer->typeLiabilities->map(function ($liability) {
                return [
                    'id' => $liability->id,
                    'name' => $liability->name,
                    'code' => $liability->code
                ];
            }),
            'economic_activities' => $customer->economicActivities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'code' => $activity->code
                ];
            }),
            'merchant_registration' => $customer->merchant_registration,
            'address' => $customer->address,
            'location_city' => $customer->location ? [
                'id' => $customer->location->id,
                'name' => $customer->location->name,
                'code' => $customer->location->code,
                'location_department' => $customer->location->department ? [
                    'id' => $customer->location->department->id,
                    'name' => $customer->location->department->name,
                    'code' => $customer->location->department->code,
                    'location_country' => $customer->location->department->country ? [
                        'id' => $customer->location->department->country->id,
                        'name' => $customer->location->department->country->name,
                        'code' => $customer->location->department->country->code
                    ] : null
                ] : null
            ] : null,
            'postal_code' => $customer->postal_code,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'status' => (bool) $customer->status,
            'created_at' => $customer->created_at,
            'updated_at' => $customer->updated_at
        ];
    }

    public function index()
    {
        try {
            $customers = Customer::with([
                'company',
                'typeOrganization',
                'typeDocument',
                'typeRegime',
                'location.department.country'
            ])->get();

            $transformedCustomers = $customers->map(function ($customer) {
                return $this->transformCustomer($customer);
            });

            return response()->json([
                'message' => 'Customers retrieved successfully',
                'customers' => $transformedCustomers
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving customers: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error retrieving customers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'type_organization_id' => 'required|exists:type_organizations,id',
            'type_document_id' => 'required|exists:type_documents,id',
            'document_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers')->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                                ->where('type_document_id', $request->type_document_id);
                })
            ],
            'dv' => 'required|integer|min:0|max:9',
            'business_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'type_regime_id' => 'required|exists:type_regimes,id',
            'type_liabilities' => 'required|array',
            'type_liabilities.*' => 'exists:type_liabilities,id',
            'economic_activities' => 'nullable|array',
            'economic_activities.*' => 'exists:economic_activities,id',
            'merchant_registration' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'location_id' => 'required|exists:cities,id',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers')->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id);
                })
            ],
            'status' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            if (isset($data['status'])) {
                $data['status'] = filter_var($data['status'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            // Procesar type_liabilities y economic_activities
            if (isset($data['type_liabilities'])) {
                $liabilities = is_array($data['type_liabilities'])
                    ? $data['type_liabilities']
                    : json_decode($data['type_liabilities'], true);
                
                if (is_array($liabilities)) {
                    $data['type_liabilities'] = $liabilities;
                }
            }

            if (isset($data['economic_activities'])) {
                $activities = is_array($data['economic_activities'])
                    ? $data['economic_activities']
                    : json_decode($data['economic_activities'], true);
                
                if (is_array($activities)) {
                    $data['economic_activities'] = $activities;
                }
            }

            $customer = Customer::create($data);

            // Sincronizar relaciones después de crear
            if (isset($liabilities)) {
                $customer->typeLiabilities()->sync($liabilities);
            }

            if (isset($activities)) {
                $customer->economicActivities()->sync($activities);
            }

            DB::commit();

            // Recargar el modelo con todas sus relaciones
            $customer = Customer::with([
                'company',
                'typeOrganization',
                'typeDocument',
                'typeRegime',
                'typeLiabilities',
                'economicActivities',
                'location.department.country'
            ])->find($customer->id);

            return response()->json([
                'message' => 'Customer created successfully',
                'data' => $this->transformCustomer($customer)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating customer: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error creating customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $customer = Customer::with([
                'company',
                'typeOrganization',
                'typeDocument',
                'typeRegime',
                'location.department.country'
            ])->find($id);

            if (!$customer) {
                return response()->json([
                    'message' => 'Customer not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Customer retrieved successfully',
                'customer' => $this->transformCustomer($customer)
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving customer: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error retrieving customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'company_id' => 'sometimes|required|exists:companies,id',
            'type_organization_id' => 'sometimes|required|exists:type_organizations,id',
            'type_document_id' => 'sometimes|required|exists:type_documents,id',
            'document_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('customers')->where(function ($query) use ($request, $customer) {
                    return $query->where('company_id', $request->company_id ?? $customer->company_id)
                                ->where('type_document_id', $request->type_document_id ?? $customer->type_document_id);
                })->ignore($id)
            ],
            'dv' => 'sometimes|required|integer|min:0|max:9',
            'business_name' => 'sometimes|required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'type_regime_id' => 'sometimes|required|exists:type_regimes,id',
            'type_liabilities' => 'sometimes|required|array',
            'type_liabilities.*' => 'exists:type_liabilities,id',
            'economic_activities' => 'nullable|array',
            'economic_activities.*' => 'exists:economic_activities,id',
            'merchant_registration' => 'nullable|string|max:255',
            'address' => 'sometimes|required|string|max:255',
            'location_id' => 'sometimes|required|exists:cities,id',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('customers')->where(function ($query) use ($request, $customer) {
                    return $query->where('company_id', $request->company_id ?? $customer->company_id);
                })->ignore($id)
            ],
            'status' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->except(['_method']);
            
            // Convertir valores booleanos
            if (isset($data['status'])) {
                $data['status'] = filter_var($data['status'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            // Procesar type_liabilities
            if (isset($data['type_liabilities'])) {
                $liabilities = is_array($data['type_liabilities'])
                    ? $data['type_liabilities']
                    : json_decode($data['type_liabilities'], true);
                
                if (is_array($liabilities)) {
                    $data['type_liabilities'] = $liabilities;
                    $customer->typeLiabilities()->sync($liabilities);
                }
            }

            // Procesar economic_activities
            if (isset($data['economic_activities'])) {
                $activities = is_array($data['economic_activities'])
                    ? $data['economic_activities']
                    : json_decode($data['economic_activities'], true);
                
                if (is_array($activities)) {
                    $data['economic_activities'] = $activities;
                    $customer->economicActivities()->sync($activities);
                }
            }

            $customer->update($data);

            DB::commit();

            // Recargar el modelo
            $customer = Customer::with([
                'company',
                'typeOrganization',
                'typeDocument',
                'typeRegime',
                'typeLiabilities',
                'economicActivities',
                'location.department.country'
            ])->find($id);

            return response()->json([
                'message' => 'Customer updated successfully',
                'data' => $this->transformCustomer($customer)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating customer: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data ?? null
            ]);
            return response()->json([
                'message' => 'Error updating customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'message' => 'Customer not found'
                ], 404);
            }

            DB::beginTransaction();

            $customer->delete();

            DB::commit();

            return response()->json([
                'message' => 'Customer deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting customer: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error deleting customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
