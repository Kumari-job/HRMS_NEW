<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'image' => $this->image,
            'mobile' => $this->mobile,
            'address' => $this->address,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'image_path' => $this->image_path,
            'marital_status' => $this->marital_status,
            'religion' => $this->religion
        ];
    }
}
