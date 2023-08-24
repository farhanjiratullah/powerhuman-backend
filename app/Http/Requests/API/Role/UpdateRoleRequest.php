<?php

namespace App\Http\Requests\API\Role;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $company = Company::find($this->company_id);

        return auth()->check() && auth()->user()->companies->contains($company);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'name' => 'required|string|max:255',
            'responsibilities' => 'required|array',
            'responsibilities.*.id' => 'nullable|integer|exists:responsibilities,id',
            'responsibilities.*.name' => 'required|string|max:255',
        ];
    }
}
