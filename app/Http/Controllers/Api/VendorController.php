<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorRequest;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $company_id = Auth::user()->selectedCompany->company_id;

        $query = Vendor::where('company_id', $company_id);

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
        $vendors = $query->latest()->paginate($request->page_size ?? 10);
        return VendorResource::collection($vendors);
    }

    public function store(VendorRequest $request)
    {
        $company_id = Auth::user()->selectedCompany->company_id;
        $vendor = new Vendor($request->validated());
        $vendor->created_by = Auth::id();
        $vendor->company_id = $company_id;
        $vendor->save();
        return response()->json(['success' => true,'message'=>'Vendor created successfully'],201);
    }

    public function show($id)
    {
        $company_id = Auth::user()->selectedCompany->company_id;
        $vendor = Vendor::with('createdBy','updatedBy')->where('company_id',$company_id)->find($id);
        if(!$vendor){
            return response()->json(['error' => true,'message'=>'Vendor not found'],404);
        }
        return new VendorResource($vendor);
    }

    public function update(VendorRequest $request, $id)
    {
        try {
            $company_id = Auth::user()->selectedCompany->company_id;
            $vendor = Vendor::with('createdBy', 'updatedBy')->where('company_id', $company_id)->find($id);
            if (!$vendor) {
                return response()->json(['error' => true, 'message' => 'Vendor not found'], 404);
            }
            $vendor->updated_by = Auth::id();
            $vendor->update($request->validated());
            return response()->json(['success' => true, 'message' => 'Vendor updated successfully'], 200);
        }catch (\Exception $exception){
            Log::error("Unable to update vendor: ".$exception->getMessage());
            return response()->json(['error' => true, 'message' => "Unable to update vendor"], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $company_id = Auth::user()->selectedCompany->company_id;
            $vendor = Vendor::where('company_id', $company_id)->find($id);
            if (!$vendor) {
                return response()->json(['error' => true, 'message' => 'Vendor not found'], 404);
            }
            $vendor->delete();
            return response()->json(['success' => true, 'message' => 'Vendor deleted successfully'], 200);
        }catch (\Exception $exception){
            Log::error("Unable to delete vendor: ".$exception->getMessage());
            return response()->json(['error' => true, 'message' => "Unable to delete vendor"], 400);
        }
    }
}
