<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Http\Requests\API\StoreCompanyRequest;
use App\Http\Requests\API\UpdateCompanyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo')->store('assets/logo');
                $data['logo'] = $logo;
            }

            $company = Company::create($data);

            $attachCompanyFromLoggedInUser = auth()->user()->companies()->attach($company->id);

            DB::commit();

            return ResponseFormatter::success($company, 'Successfully created a new company', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($logo)) {
                Storage::delete($logo);
            }

            return ResponseFormatter::error(['errors' => [$e->getMessage()]], $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        //
    }
}
