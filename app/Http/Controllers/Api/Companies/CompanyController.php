<?php

namespace App\Http\Controllers\Api\Companies;

use App\Http\Controllers\Controller;
use App\Models\Companies\Company;
use App\Models\Catalogs\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    protected function handleFileUpload($file, $company, $type = 'logo')
    {
        try {
            Log::info("Starting file upload", [
                'type' => $type,
                'company_id' => $company->id,
                'original_name' => $file->getClientOriginalName()
            ]);

            // Determinar configuración según el tipo
            $config = [
                'logo' => [
                    'disk' => 'public',
                    'directory' => 'companies/logos/' . $company->id,
                    'filename' => $company->document_number . '_logo_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension(),
                    'field' => 'logo_path',
                    'is_public' => true
                ],
                'certificate' => [
                    'disk' => 'local',
                    'directory' => 'companies/certificates/' . $company->id,
                    'filename' => $company->document_number . '_certificate_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension(),
                    'field' => 'certificate_path',
                    'is_public' => false
                ]
            ][$type];

            Log::info("Config determined", [
                'type' => $type,
                'config' => $config
            ]);

            // Eliminar todos los archivos existentes en el directorio
            if (Storage::disk($config['disk'])->exists($config['directory'])) {
                $files = Storage::disk($config['disk'])->files($config['directory']);
                foreach ($files as $existingFile) {
                    Storage::disk($config['disk'])->delete($existingFile);
                    Log::info("Deleted existing file", ['file' => $existingFile]);
                }
            }

            // Construir rutas
            $relativePath = $config['directory'] . '/' . $config['filename'];
            Storage::disk($config['disk'])->put($relativePath, file_get_contents($file));

            // Actualizar la ruta en la base de datos
            Log::info("Updating database", [
                'field' => $config['field'],
                'path' => $relativePath
            ]);

            $company->update([$config['field'] => $relativePath]);

            Log::info("File upload completed successfully", [
                'type' => $type,
                'path' => $relativePath
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Error saving {$type}: " . $e->getMessage(), [
                'company_id' => $company->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function transformCompany($company)
    {
        return [
            'id' => $company->id,
            'type_organization' => [
                'id' => $company->typeOrganization->id,
                'name' => $company->typeOrganization->name,
                'code' => $company->typeOrganization->code
            ],
            'type_document' => [
                'id' => $company->typeDocument->id,
                'name' => $company->typeDocument->name,
                'code' => $company->typeDocument->code
            ],
            'document_number' => $company->document_number,
            'dv' => $company->dv,
            'business_name' => $company->business_name,
            'trade_name' => $company->trade_name,
            'type_regime' => [
                'id' => $company->typeRegime->id,
                'name' => $company->typeRegime->name,
                'code' => $company->typeRegime->code
            ],
            'type_liabilities' => $company->typeLiabilities->map(function ($liability) {
                return [
                    'id' => $liability->id,
                    'name' => $liability->name,
                    'code' => $liability->code
                ];
            }),
            'economic_activities' => $company->economicActivities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'code' => $activity->code
                ];
            }),
            'merchant_registration' => $company->merchant_registration,
            'address' => $company->address,
            'location_city' => [
                'id' => $company->location->id,
                'name' => $company->location->name,
                'code' => $company->location->code,
                'location_department' => [
                    'id' => $company->location->department->id,
                    'name' => $company->location->department->name,
                    'code' => $company->location->department->code,
                    'location_country' => [
                        'id' => $company->location->department->country->id,
                        'name' => $company->location->department->country->name,
                        'code' => $company->location->department->country->code
                    ]
                ]
            ],
            'postal_code' => $company->postal_code,
            'phone' => $company->phone,
            'email' => $company->email,
            'logo_path' => $company->logo_path,
            'certificate_path' => $company->certificate_path,
            'software_id' => $company->software_id,
            'test_set_id' => $company->test_set_id,
            'environment' => $company->environment,
            'subdomain' => $company->subdomain,
            'branches' => $company->branches->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'code' => $branch->code,
                    'name' => $branch->name,
                    'address' => $branch->address,
                    'city' => [
                        'id' => $branch->city->id,
                        'name' => $branch->city->name,
                        'code' => $branch->city->code
                    ],
                    'phone' => $branch->phone,
                    'email' => $branch->email,
                    'manager_name' => $branch->manager_name,
                    'cost_center' => $branch->cost_center,
                    'is_main' => $branch->is_main,
                    'status' => $branch->status,
                    'created_at' => $branch->created_at,
                    'updated_at' => $branch->updated_at
                ];
            }),
            'status' => $company->status,
            'created_at' => $company->created_at,
            'updated_at' => $company->updated_at,
        ];
    }

    public function index()
    {
        $companies = Company::latest()->get();

        return response()->json([
            'message' => 'Companies retrieved successfully',
            'companies' => $companies->map(function ($company) {
                return $this->transformCompany($company);
            })
        ]);
    }

    public function create()
    {
        // Obtener departamentos con sus ciudades y país
        $departments = Department::with(['country:id,name,code', 'cities:id,department_id,name,code'])
            ->select('id', 'country_id', 'name', 'code')
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'country' => $department->country,
                    'cities' => $department->cities
                ];
            });

        return response()->json([
            'message' => 'Form data retrieved successfully',
            'departments' => $departments
        ]);
    }

    public function store(Request $request)
    {
        Log::info("Incoming company creation request", [
            'has_logo' => $request->hasFile('logo'),
            'has_certificate' => $request->hasFile('certificate'),
            'files' => $request->allFiles()
        ]);

        $validator = Validator::make($request->all(), [
            'type_organization_id' => 'required|exists:type_organizations,id',
            'type_document_id' => 'required|exists:type_documents,id',
            'document_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('companies')->where(function ($query) use ($request) {
                    return $query->where('type_document_id', $request->type_document_id);
                })
            ],
            'dv' => 'required|integer|min:0|max:9',
            'business_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'type_regime_id' => 'required|exists:type_regimes,id',
            'type_liabilities' => 'required|array',
            'type_liabilities.*' => 'exists:type_liabilities,id',
            'economic_activities' => 'required|array',
            'economic_activities.*' => 'exists:economic_activities,id',
            'merchant_registration' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'location_id' => 'required|exists:cities,id',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:companies',
            'logo' => 'nullable|image|max:2048',
            'certificate' => [
                'nullable',
                'file',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if (!$value) return;

                    $extension = strtolower($value->getClientOriginalExtension());
                    $mimeType = $value->getMimeType();

                    $validExtensions = ['p12', 'pfx'];
                    $validMimes = [
                        'application/x-pkcs12',
                        'application/x-pkcs12-file',
                        'application/octet-stream'
                    ];

                    if (!in_array($extension, $validExtensions)) {
                        $fail('El certificado debe ser un archivo .p12 o .pfx');
                        return;
                    }

                    if (!in_array($mimeType, $validMimes)) {
                        Log::warning('Certificate validation: Invalid MIME type', [
                            'mime' => $mimeType,
                            'extension' => $extension,
                            'original_name' => $value->getClientOriginalName()
                        ]);
                    }
                }
            ],
            'certificate_password' => 'required_with:certificate|nullable|string|max:255',
            'software_id' => 'nullable|string|max:255',
            'software_pin' => 'nullable|string|max:255',
            'test_set_id' => 'nullable|string|max:255',
            'environment' => 'required|integer|in:1,2',
            'status' => 'boolean',
            'subdomain' => [
                'required',
                'string',
                'regex:/^[a-z0-9]{3,12}$/',
                'min:3',
                'max:12',
                'unique:companies',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->except(['logo', 'certificate']);

            // Convertir el subdomain a minúsculas
            $data['subdomain'] = strtolower($data['subdomain']);

            if (!empty($data['certificate_password'])) {
                $data['certificate_password'] = encrypt($data['certificate_password']);
            }
            if (!empty($data['software_pin'])) {
                $data['software_pin'] = encrypt($data['software_pin']);
            }

            $company = Company::create($data);

            Log::info("Company created, processing files", [
                'company_id' => $company->id,
                'has_logo' => $request->hasFile('logo'),
                'has_certificate' => $request->hasFile('certificate')
            ]);

            if ($request->hasFile('logo')) {
                $this->handleFileUpload($request->file('logo'), $company, 'logo');
            }

            if ($request->hasFile('certificate')) {
                $this->handleFileUpload($request->file('certificate'), $company, 'certificate');
            }

            if ($request->has('type_liabilities')) {
                $company->typeLiabilities()->sync($request->type_liabilities);
            }

            if ($request->has('economic_activities')) {
                $company->economicActivities()->sync($request->economic_activities);
            }

            DB::commit();

            // Recargar el modelo con todas sus relaciones
            $company = Company::with([
                'typeOrganization',
                'typeDocument',
                'typeRegime',
                'typeLiabilities',
                'economicActivities',
                'location.department.country',
                'branches.city'
            ])->find($company->id);

            return response()->json([
                'message' => 'Company created successfully',
                'company' => $this->transformCompany($company)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating company: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error creating company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $company = Company::with(['branches.city'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->transformCompany($company)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la compañía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'subdomain' => [
                'sometimes',
                'string',
                'min:3',
                'max:12',
                'regex:/^[a-z0-9]+$/',
                Rule::unique('companies')->ignore($company->id)
            ],
            'type_organization_id' => 'sometimes|exists:type_organizations,id',
            'type_document_id' => 'sometimes|exists:type_documents,id',
            'document_number' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('companies')->where(function ($query) use ($request, $company) {
                    return $query
                        ->where('type_document_id', $request->type_document_id)
                        ->where('id', '!=', $company->id);
                })
            ],
            'dv' => 'sometimes|integer|min:0|max:9',
            'business_name' => 'sometimes|string|max:255',
            'trade_name' => 'sometimes|nullable|string|max:255',
            'type_regime_id' => 'sometimes|exists:type_regimes,id',
            'type_liabilities' => 'sometimes|array',
            'type_liabilities.*' => 'exists:type_liabilities,id',
            'economic_activities' => 'sometimes|array',
            'economic_activities.*' => 'exists:economic_activities,id',
            'merchant_registration' => 'sometimes|string|max:255',
            'address' => 'sometimes|nullable|string|max:255',
            'location_id' => 'sometimes|exists:cities,id',
            'postal_code' => 'sometimes|nullable|string|max:10',
            'phone' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('companies')->ignore($company->id)
            ],
            'logo' => 'sometimes|nullable|image|max:2048',
            'certificate' => [
                'sometimes',
                'nullable',
                'file',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if (!$value) return;

                    $extension = strtolower($value->getClientOriginalExtension());
                    $mimeType = $value->getMimeType();

                    $validExtensions = ['p12', 'pfx'];
                    $validMimes = [
                        'application/x-pkcs12',
                        'application/x-pkcs12-file',
                        'application/octet-stream'
                    ];

                    if (!in_array($extension, $validExtensions)) {
                        $fail('El certificado debe ser un archivo .p12 o .pfx');
                        return;
                    }

                    if (!in_array($mimeType, $validMimes)) {
                        Log::warning('Certificate validation: Invalid MIME type', [
                            'mime' => $mimeType,
                            'extension' => $extension,
                            'original_name' => $value->getClientOriginalName()
                        ]);
                    }
                }
            ],
            'certificate_password' => 'sometimes|required_with:certificate|nullable|string|max:255',
            'software_id' => 'sometimes|nullable|string|max:255',
            'software_pin' => 'sometimes|nullable|string|max:255',
            'test_set_id' => 'sometimes|nullable|string|max:255',
            'environment' => 'sometimes|integer|in:1,2',
            'status' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Obtener los datos del request
            $data = $validator->validated();

            // Convertir el subdomain a minúsculas si existe
            if (isset($data['subdomain'])) {
                $data['subdomain'] = strtolower($data['subdomain']);
            }

            // Convertir valores booleanos
            if (isset($data['status'])) {
                $data['status'] = filter_var($data['status'], FILTER_VALIDATE_BOOLEAN);
            }

            Log::info('Datos a actualizar:', [
                'data' => $data,
                'files' => [
                    'has_logo' => $request->hasFile('logo'),
                    'has_certificate' => $request->hasFile('certificate')
                ]
            ]);

            // Encriptar datos sensibles
            if (!empty($data['certificate_password'])) {
                $data['certificate_password'] = encrypt($data['certificate_password']);
            }
            if (!empty($data['software_pin'])) {
                $data['software_pin'] = encrypt($data['software_pin']);
            }

            // Actualizar datos básicos
            if (!empty($data)) {
                $company->update($data);
            }

            // Procesar archivos
            if ($request->hasFile('logo')) {
                $this->handleFileUpload($request->file('logo'), $company, 'logo');
            }

            if ($request->hasFile('certificate')) {
                $this->handleFileUpload($request->file('certificate'), $company, 'certificate');
            }

            // Sincronizar relaciones
            if ($request->has('type_liabilities')) {
                $company->typeLiabilities()->sync($request->type_liabilities);
            }

            if ($request->has('economic_activities')) {
                $company->economicActivities()->sync($request->economic_activities);
            }

            DB::commit();

            // Recargar el modelo con todas sus relaciones
            $company = Company::with([
                'typeOrganization',
                'typeDocument',
                'typeRegime',
                'typeLiabilities',
                'economicActivities',
                'location.department.country',
                'branches.city'
            ])->find($id);

            return response()->json([
                'message' => 'Company updated successfully',
                'company' => $this->transformCompany($company)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating company: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data ?? null
            ]);
            return response()->json([
                'message' => 'Error updating company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'message' => 'Company not found'
                ], 404);
            }

            DB::beginTransaction();

            // Eliminar archivos si existen
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            if ($company->certificate_path) {
                Storage::disk('local')->delete($company->certificate_path);
            }

            // Eliminar relaciones
            $company->typeLiabilities()->detach();
            $company->economicActivities()->detach();

            // Cambiar status a false antes del soft delete
            $company->status = false;
            $company->save();

            // Soft delete
            $company->delete();

            DB::commit();

            return response()->json([
                'message' => 'Company deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting company: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error deleting company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCitiesByDepartment($departmentId)
    {
        $department = Department::with(['cities:id,department_id,name,code'])
            ->select('id', 'name', 'code')
            ->findOrFail($departmentId);

        return response()->json([
            'message' => 'Cities retrieved successfully',
            'cities' => $department->cities
        ]);
    }
}
