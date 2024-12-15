<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeOnboardingRequest;
use App\Models\EmployeeOnboarding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmployeeOnboardingController extends Controller
{
    public function index()
    {

    }

    public function store(EmployeeOnboardingRequest $request)
    {
        try {
            $employeeOnboarding = new EmployeeOnboarding($request->all());
            $employeeOnboarding->created_by = Auth::id();
            $employeeOnboarding->save();
            return response()->json(['success' => true, 'message' => 'Employee Onboarding created successfully.'], 201);
        }catch (\Exception $exception){
            Log::error("Unable to create Employee Onboarding: {$exception->getMessage()}");
            return response()->json(['error' => true, 'message' => 'Unable to create employee onboarding'], 400);
        }
    }

    public function update(EmployeeOnboardingRequest $request, $id)
    {
        try{
            $employeeOnboarding = EmployeeOnboarding::find($id);
            if(!$employeeOnboarding){
                return response()->json(['error' => true, 'message' => 'Employee Onboarding not found'], 404);
            }
            $employeeOnboarding->updated_by = Auth::id();
            $employeeOnboarding->update($request->all());
            return response()->json(['success' => true, 'message' => 'Employee Onboarding updated successfully.'], 200);
        }catch (\Exception $exception){
            Log::error("Unable to update Employee Onboarding: {$exception->getMessage()}");
            return response()->json(['error' => true, 'message' => 'Unable to update employee onboarding'], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $employeeOnboarding = EmployeeOnboarding::find($id);
            if (!$employeeOnboarding) {
                return response()->json(['error' => true, 'message' => 'Employee Onboarding not found'], 404);
            }
            $employeeOnboarding->delete();
            return response()->json(['success' => true, 'message' => 'Employee Onboarding deleted successfully.'], 200);
        }catch (\Exception $exception){
            Log::error("Unable to delete Employee Onboarding: {$exception->getMessage()}");
            return response()->json(['error' => true, 'message' => 'Unable to delete employee onboarding'], 400);
        }
    }
}
