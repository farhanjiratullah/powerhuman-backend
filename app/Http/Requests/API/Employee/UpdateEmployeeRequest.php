<?php

namespace App\Http\Requests\API\Employee;

use App\Models\Company;
use App\Models\Role;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $team = Team::find($this->team_id);
        $role = Role::find($this->role_id);

        $teamCompany = Company::find($team->company_id);
        $roleCompany = Company::find($role->company_id);

        return auth()->check() && auth()->user()->companies->contains($teamCompany) && auth()->user()->companies->contains($roleCompany);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => 'required|integer|exists:teams,id',
            'role_id' => 'required|integer|exists:roles,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $this->employee->id,
            'gender' => 'required|string|in:male,female',
            'age' => 'required|integer',
            'phone' => 'nullable|string|max:13',
            'photo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp'
        ];
    }
}
