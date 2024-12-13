<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContract extends Model
{
    public $table = 'employee_contracts';
    public $fillable = [
        'employee_id',
        'contract_type',
        'job_description',
        'gross_salary',
        'basic_salary',
        'pf_from_employee',
        'pf_from_company',
        'gratuity',
        'cit_percentage',
        'cit_amount',
        'ssf_amount',
        'created_by',
        'updated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }

    public function createdBy():BelongsTo
    {
        return $this->belongsTo(User::class,'created_by');
    }
    public function updatedBy():BelongsTo
    {
        return $this->belongsTo(User::class,'updated_by');
    }
}
