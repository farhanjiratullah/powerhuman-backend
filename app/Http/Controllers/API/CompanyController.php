<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Http\Requests\API\Company\StoreCompanyRequest;
use App\Http\Requests\API\Company\UpdateCompanyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $name = $request->query('name');

        $companies = Company::query()
            ->whereHas('users', function ($query) {
                return $query->whereUserId(auth()->id());
            })
            ->when($name, function ($query) use ($name) {
                return $query->where('name', 'like', "%{$name}%");
            })
            ->paginate($limit);

        return ResponseFormatter::success($companies, 'Successfully fetched all the companies');
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
                $logo = $request->file('logo')->store('assets/logos');
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
        if (!auth()->user()->companies->contains($company)) {
            return ResponseFormatter::error(['company' => ['You do not own this company']], 'You do not own this company', 403);
        }
        return ResponseFormatter::success($company, 'Successfully fetched the company');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        if (!auth()->user()->companies->contains($company)) {
            return ResponseFormatter::error(['company' => ['You do not own this company']], 'You do not own this company', 403);
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo')->store('assets/logos');
                $data['logo'] = $logo;

                Storage::delete($company->logo);
            } else {
                $data['logo'] = $company->logo;
            }

            $company->update($data);

            DB::commit();

            return ResponseFormatter::success($company, 'Successfully updated the company');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($logo)) {
                Storage::delete($logo);
            }

            return ResponseFormatter::error(['errors' => [$e->getMessage()]], $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        //
    }
}
