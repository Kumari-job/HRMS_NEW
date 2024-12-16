<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $assetId = $this->route('id');
        return [
            'asset_category_id' => 'required|exists:asset_categories,id',
            'vendor_id' => 'required|exists:vendors,id',
            'code' => [
                'required',
                'string',
                Rule::unique('assets', 'code')->ignore($assetId)
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'cost' => 'required|numeric',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string',
            'purchased_at' => 'required|date',
            'warranty_end_at' => 'nullable|date',
            'warranty_image' => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'guarantee_end_at' => 'nullable|date',
            'guarantee_image' => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'status' => 'required|in:new,used,disposed,damaged,maintenance',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            $response = [
                'error' => true,
                'errors' => $validator->errors(),
                'message' => 'There are some issues in the form.'
            ];

            throw new HttpResponseException(response()->json($response, 422));
        }

        parent::failedValidation($validator);
    }
}
