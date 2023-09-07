<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Http\Requests\API\Role\StoreRoleRequest;
use App\Http\Requests\API\Role\UpdateRoleRequest;
use App\Models\Company;
use App\Models\Responsibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $search = $request->query('search');

        $roles = Role::query()
            ->withCount('employees')
            ->whereHas('company', function ($query) {
                return $query->whereHas('users', function ($query) {
                    return $query->whereUserId(auth()->id());
                });
            })
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->paginate($limit);

        return ResponseFormatter::success($roles, 'Successfully fetched all the roles');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $role = Role::create($data);

            $responsibilities = collect($data['responsibilities'])->map(function ($responsibility) use ($role): array {
                return [
                    'role_id' => $role->id,
                    'name' => $responsibility["name"],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();

            Responsibility::insert($responsibilities);

            DB::commit();

            return ResponseFormatter::success($role, 'Successfully created a new role', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($icon)) {
                Storage::delete($icon);
            }

            return ResponseFormatter::error(['errors' => [$e->getMessage()]], $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        if (!auth()->user()->companies->contains($role->company)) {
            return ResponseFormatter::error(['role' => ['You do not own this role']], 'You do not own this role', 403);
        }

        return ResponseFormatter::success($role->load(['responsibilities', 'employees']), 'Successfully fetched the role');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        if (!auth()->user()->companies->contains($role->company)) {
            return ResponseFormatter::error(['role' => ['You do not own this role']], 'You do not own this role', 403);
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();

            $role->update($data);

            $responsibilitiesFilterIdWhereNotNull = collect($data['responsibilities'])->filter(fn ($responsibility): bool => $responsibility['id'] !== null);

            $responsibilitiesToUpdate =
                $responsibilitiesFilterIdWhereNotNull->map(function ($responsibility) use ($role): array {
                    return [
                        'id' => $responsibility['id'],
                        'role_id' => $role->id,
                        'name' => $responsibility["name"],
                        'updated_at' => now()
                    ];
                })->toArray();

            $responsibilitiesWhereNotInPresentRequest = $responsibilitiesFilterIdWhereNotNull->pluck('id')->toArray();

            $responsibilitiesToInsert = collect($data['responsibilities'])->filter(fn ($responsibility): bool => $responsibility['id'] === null)->map(function ($responsibility) use ($role): array {
                return [
                    'role_id' => $role->id,
                    'name' => $responsibility["name"],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();

            Responsibility::whereRoleId($role->id)->whereNotIn('id', $responsibilitiesWhereNotInPresentRequest)->delete();

            Responsibility::upsert($responsibilitiesToUpdate, ['id'], ['name', 'updated_at']);
            Responsibility::insert($responsibilitiesToInsert);

            DB::commit();

            return ResponseFormatter::success($role, 'Successfully updated the role');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($icon)) {
                Storage::delete($icon);
            }

            return ResponseFormatter::error(['errors' => [$e->getMessage()]], $e->getMessage(), 500);
        }
    }

    public function destroy(Role $role)
    {
        if (!auth()->user()->companies->contains($role->company)) {
            return ResponseFormatter::error(['role' => ['You do not own this role']], 'You do not own this role', 403);
        }

        $role->delete();

        return ResponseFormatter::success(message: 'Successfully deleted the role', code: 204);
    }

    public function all(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->whereHas('company.users', function ($query) {
                return $query->whereUserId(auth()->id());
            })
            ->get();

        return ResponseFormatter::success($roles, 'Successfully fetched all the roles');
    }

    public function getAllRolesBasedOnCompanyId(Request $request, Company $company): JsonResponse
    {
        if (!auth()->user()->companies->contains($company->id)) {
            return ResponseFormatter::error(['role' => ['You do not own this role']], 'You do not own this role', 403);
        }

        $roles = Role::with('responsibilities')->whereCompanyId($company->id)
            ->get();

        return ResponseFormatter::success($roles, 'Successfully fetched all the roles');
    }
}
