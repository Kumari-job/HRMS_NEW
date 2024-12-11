<?php

namespace App\Http\Controllers\Api;

use App\Helpers\MessageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $company_id = Auth::user()->selectedCompany->company_id;
        $departments = Department::
            whereHas('branch', fn($q) => $q->where('company_id', $company_id))
            ->where('company_id', $company_id)->where(function ($query) use ($request) {
                if ($request->filled('branch_id')) {
                    $query->where('branch_id', $request->branch_id);
                }
                if ($request->filled('name')) {
                    $query->where('name', 'LIKE', '%' . $request->name . '%');
                }
            })->where('company_id', $company_id)->latest()
            ->paginate($request->page_size ?? 10);

        return DepartmentResource::collection($departments);

    }

    public function store(DepartmentRequest $request)
    {
        if (Department::where('branch_id', $request->branch_id)->where('name', $request->name)->exists()) {
            return response()->json(['error' => true, 'message' => 'Department name already exists'], 422);
        }
        $data = $request->only(['name', 'branch_id']);
        $data['created_by'] = Auth::id();

        Department::create($data);
        return response()->json(['success' => true, 'message' => 'Department created successfully.'], 201);
    }

    public function show($id)
    {
        $company_id = Auth::user()->selectedCompany->company_id;

        $department = Department::where('id', $id)
        ->whereHas('branch', fn($q) => $q->where('company_id', $company_id))
        ->first();

        if (!$department) {
            return response()->json(['error' => true, 'errors' => 'Department not found.'], 404);
        }
        return new DepartmentResource($department);
    }

    public function update(DepartmentRequest $request, $id)
    {
        $company_id = Auth::user()->selectedCompany->company_id;

        $department = Department::where('id', $id)
            ->whereHas('branch', fn($q) => $q->where('company_id', $company_id))
            ->first();
        if (!$department) {
            return response()->json(['error' => true, 'message' => 'Department not found'], 404);
        }

        $data = $request->only(['name', 'branch_id']);
        $data['updated_by'] = Auth::id();
        $department->update($data);

        return response()->json(['success' => true, 'message' => 'Department updated successfully.'], 200);
    }
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'array'
        ]);
        $ids = $request->ids;
        if ($validator->fails()) {
            return response()->json(['error' => true, 'errors' => $validator->errors(), 'message' => MessageHelper::getErrorMessage('form')], 422);
        }
        $departments = Department::whereIn('id', $ids);
        $count = $departments->count();
        if ($count > 0) {
            $deleteStatus = $departments->delete();

            return response()->json(['success' => true, 'message' => 'departments trashed successfully.'], 200);
        }
        return response()->json(['error' => true, 'message' => 'departments not found.'], 400);
    }

    public function trashed(Request $request)
    {
        $query = Department::onlyTrashed();
        if (!empty($request->except('page', 'page_size'))) {
            foreach ($request->except('page', 'page_size') as $key => $value) {
                if (isset($value) && !empty($value)) {
                    if (in_array($key, ['id', 'company_id'])) {
                        $query->where($key, $value);
                    } else {
                        $query->where($key, 'LIKE', '%' . $value . '%');
                    }
                }
            }
        }
        $company_id = Auth::user()->selectedCompany->company_id;
        $departments = $query->where('company_id', $company_id)->latest()->paginate($request->page_size ?? 10);
        return DepartmentResource::collection($departments);
    }
    public function restore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'array'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'errors' => $validator->errors(), 'message' => MessageHelper::getErrorMessage('form')], 422);
        }
        $ids = $request->ids;
        Department::withTrashed()->whereIn('id', $ids)->restore();
        return response()->json(['success' => true, 'message' => 'Department restored successfully.'], 200);
    }

    public function forceDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'array'
        ]);
        $ids = $request->ids;
        if ($validator->fails()) {
            return response()->json(['error' => true, 'errors' => $validator->errors(), 'message' => MessageHelper::getErrorMessage('form')], 422);
        }
        $departments = Department::withTrashed()->whereIn('id', $ids);
        $count = $departments->count();
        if ($count > 0) {

            $departments->forceDelete();
            return response()->json(['success' => true, 'message' => 'departments deleted successfully.'], 200);
        }
        return response()->json(['error' => true, 'message' => 'departments not found.'], 404);
    }
}