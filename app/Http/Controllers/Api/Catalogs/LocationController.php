<?php

namespace App\Http\Controllers\Api\Catalogs;

use App\Http\Controllers\Controller;
use App\Models\Catalogs\Department;
use App\Models\Catalogs\City;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function departments()
    {
        $departments = Department::with('country:id,name,code')
            ->select('id', 'country_id', 'name', 'code')
            ->get();

        return response()->json([
            'message' => 'Departments retrieved successfully',
            'departments' => $departments
        ]);
    }

    public function citiesByDepartment($departmentId)
    {
        $department = Department::with(['cities:id,department_id,name,code', 'country:id,name,code'])
            ->select('id', 'country_id', 'name', 'code')
            ->findOrFail($departmentId);

        return response()->json([
            'message' => 'Cities retrieved successfully',
            'department' => $department->only(['id', 'name', 'code']),
            'country' => $department->country->only(['id', 'name', 'code']),
            'cities' => $department->cities
        ]);
    }
}
