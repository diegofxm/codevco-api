<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['role', 'typeDocument'])->get();
        return response()->json(['users' => $users]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'type_document_id' => 'required|exists:type_documents,id',
            'document_number' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'status' => 'boolean'
        ]);

        $user = User::create([
            'role_id' => $request->role_id,
            'type_document_id' => $request->type_document_id,
            'document_number' => $request->document_number,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->status ?? true
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load(['role', 'typeDocument'])
        ], 201);
    }

    public function show($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'user' => $user->load(['role', 'typeDocument'])
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $request->validate([
            'role_id' => 'exists:roles,id',
            'type_document_id' => 'exists:type_documents,id',
            'document_number' => 'string|max:20',
            'name' => 'string|max:255',
            'email' => ['string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'status' => 'boolean'
        ]);

        $userData = $request->except('password');
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load(['role', 'typeDocument'])
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}