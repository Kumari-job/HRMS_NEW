<?php

namespace App\Http\Controllers\Api;

use App\Helpers\DirectoryPathHelper;
use App\Helpers\MessageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Traits\FileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    use FileHelper;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $company_id = Auth::user()->selectedCompany->company_id;

        $query = Employee::query();

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

        $employees = $query->where('company_id', $company_id)->latest()->paginate($request->page_size ?? 10);
        return EmployeeResource::collection($employees);
    }


    public function store(EmployeeRequest $request)
    {
        $company_id = Auth::user()->selectedCompany->company_id;
        if (Employee::where('company_id', $company_id)->where('name',$request['name'])->where('email',$request['email'])->exists()) {
            return response()->json(['error'=>true,"message"=>"Employee already exists"],400);
        }
        $employee = new Employee($request->except('image'));

        if ($request->hasFile('image')) {
            $path = DirectoryPathHelper::employeeImageDirectoryPath($company_id);
            $fileName = $this->fileUpload($request->file('image'), $path);
            $employee->image = $fileName;
        }

        $employee->company_id = $company_id;
        $employee->save();
        return response()->json(['success'=>true,"message"=>"Employee added successfully",'id'=>$employee->id],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $employee = Employee::with(['employeeAddress','employeeBenefit','employeeContracts','employeeDocument','employeeEducations','employeeExperiences','employeeFamilies','employeeOnboardings','employeeBanks'])->find($id);

        if(!$employee){
            return response()->json(['error'=>true,"message"=>"Employee not found"],404);
        }
        return new EmployeeResource($employee);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeRequest $request, string $id)
    {
        $employee = Employee::find($id);
        if(!$employee){
            return response()->json(['error'=>true,"message"=>"Employee not found"],404);
        }
        if (Employee::where('name',$request['name'])->where('email',$request['email'])->where('id', '!=', $id)->exists()) {
            return response()->json(['error'=>true,"message"=>"Employee already exists"],400);
        }
        $data = $request->except('image');
        if ($request->hasFile('image')) {
            $path = DirectoryPathHelper::employeeImageDirectoryPath($employee->company_id);
            if ($employee->image) {
                $this->fileDelete($path, $employee->image);
            }
            $fileName = $this->fileUpload($request->file('image'), $path);
            $data['image'] = $fileName;
        }

        $employee->update($data);
        return response()->json(['success'=>true,"message"=>"Employee updated successfully",'id'=>$employee->id],200);
    }

    public function updateImage(Request $request, string $id)
    {
        $employee = Employee::find($id);
        if(!$employee){
            return response()->json(['error'=>true,"message"=>"Employee not found"],404);
        }

        if ($request->hasFile('image')) {
            $path = DirectoryPathHelper::employeeImageDirectoryPath($employee->company_id);
            if ($employee->image) {
                $this->fileDelete($path, $employee->image);
            }
            $fileName = $this->fileUpload($request->file('image'), $path);
        }

        $employee->update(['image' => $fileName]);
        return response()->json(['success'=>true,"message"=>"Image updated successfully",'id'=>$employee->id],200);
    }

    public function removeImage(Request $request, string $id)
    {
        $employee = Employee::find($id);
        $path = DirectoryPathHelper::employeeImageDirectoryPath($employee->company_id);
        if ($employee->image) {
            $this->fileDelete($path, $employee->image);
        }
        $employee->update(['image' => null]);
        return response()->json(['success'=>true,"message"=>"Image removed successfully",'id'=>$employee->id],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'array'
        ]);
        $ids = $request->ids;
        if ($validator->fails()) {
            return response()->json(['error' => true, 'errors' => $validator->errors(), 'message' => MessageHelper::getErrorMessage('form')], 422);
        }
        $employees = Employee::whereIn('id', $ids);
        $count = $employees->count();
        if ($count > 0) {
            $deleteStatus = $employees->delete();

            return response()->json(['success' => true, 'message' => 'Employees trashed successfully.'], 200);
        }
        return response()->json(['error' => true, 'message' => 'Employees not found.'], 400);
    }

    public function trashed(Request $request)
    {
        $query = Employee::onlyTrashed();
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
        $employees = $query->where('company_id', $company_id)->latest()->paginate($request->page_size ?? 10);
        return EmployeeResource::collection($employees);
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
        Employee::withTrashed()->whereIn('id', $ids)->restore();
        return response()->json(['success' => true, 'message' => 'Employee restored successfully.'], 200);
    }

    public function forceDelete(Request $request)
    {
        $company_id = Auth::user()->selectedCompany->company_id;
        $validator = Validator::make($request->all(), [
            'ids' => 'array'
        ]);
        $ids = $request->ids;
        if ($validator->fails()) {
            return response()->json(['error' => true, 'errors' => $validator->errors(), 'message' => MessageHelper::getErrorMessage('form')], 422);
        }
        $employees = Employee::withTrashed()->whereIn('id', $ids);
        $count = $employees->count();
        if ($count > 0) {
            foreach ($employees as $employee) {
                if ($employee->image)
                {
                    $path = DirectoryPathHelper::employeeImageDirectoryPath($company_id);
                    $this->fileDelete($path, $employee->image);
                }
            }
            $employees->forceDelete();
            return response()->json(['success' => true, 'message' => 'Employees deleted successfully.'], 200);
        }
        return response()->json(['error' => true, 'message' => 'Employees not found.'], 404);
    }
}
