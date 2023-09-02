<?php

namespace App\Http\Requests\API\Team;

use App\Helpers\ResponseFormatter;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

class StoreTeamRequest extends FormRequest
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
            'icon' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
            'activated_at' => 'required|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'activated_at' => $this->activated_at ? now() : null
        ]);
    }
}
