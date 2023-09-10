<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Http\Requests\API\Employee\StoreEmployeeRequest;
use App\Http\Requests\API\Employee\UpdateEmployeeRequest;
use App\Helpers\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $search = $request->query('search');

        $totalEmployees = Employee::query()
            ->whereHas('team.company.users', function ($query) {
                return $query->whereUserId(auth()->id());
            })
            ->count();
        $isActiveEmployees = Employee::query()
            ->whereHas('team.company.users', function ($query) {
                return $query->whereUserId(auth()->id());
            })
            ->whereNotNull('verified_at')->count();
        $isInactiveEmployees = $totalEmployees - $isActiveEmployees;

        $employees = Employee::query()
            ->with('role')
            ->whereHas('team', function ($query) {
                return $query->whereHas('company', function ($query) {
                    return $query->whereHas('users', function ($query) {
                        return $query->whereId(auth()->id());
                    });
                });
            })
            ->whereHas('role', function ($query) {
                return $query->whereHas('company', function ($query) {
                    return $query->whereHas('users', function ($query) {
                        return $query->whereId(auth()->id());
                    });
                });
            })

            ->when($search, function ($query) use ($search) {
                return $query->where(function ($query) use ($search) {
                    return $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('role', function ($query) use ($search) {
                            return $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->paginate($limit);

        $employees->total_employees_count = $totalEmployees;
        $employees->is_active_employees_count = $isActiveEmployees;
        $employees->is_inactive_employees_count = $isInactiveEmployees;

        $paginationLinks = [];

        // if ($employees->currentPage() > 1) {
        $paginationLinks[] = [
            'url' => $employees->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false,
        ];
        // }

        for ($i = 1; $i <= $employees->lastPage(); $i++) {
            $paginationLinks[] = [
                'url' => $employees->url($i),
                'label' => (string)$i,
                'active' => $i === $employees->currentPage(),
            ];
        }

        // if ($employees->currentPage() < $employees->lastPage()) {
        $paginationLinks[] = [
            'url' => $employees->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false,
        ];
        // }

        $responseData = [
            'meta' => [
                'code' => 200,
                'status' => 'success',
                'message' => 'Successfully fetched all the employees'
            ],
            'data' => [
                'current_page' => $employees->currentPage(),
                'total_employees_count' => $totalEmployees,
                'is_active_employees_count' => $isActiveEmployees,
                'is_inactive_employees_count' => $isInactiveEmployees,
                'data' => $employees->items(),
                'first_page_url' => $employees->url(1),
                'from' => $employees->firstItem(),
                'last_page' => $employees->lastPage(),
                'last_page_url' => $employees->url($employees->lastPage()),
                'links' => $paginationLinks,
                'next_page_url' => $employees->nextPageUrl(),
                'path' => $employees->path(),
                'per_page' => $employees->perPage(),
                'prev_page_url' => $employees->previousPageUrl(),
                'to' => $employees->lastItem(),
                'total' => $employees->total(),
            ]
        ];

        return response()->json($responseData, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo')->store('assets/photos');
                $data['photo'] = $photo;
            }

            $employee = Employee::create($data);

            DB::commit();

            return ResponseFormatter::success($employee, 'Successfully created a new employee', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($photo)) {
                Storage::delete($photo);
            }

            return ResponseFormatter::error(['errors' => [$e->getMessage()]], $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        if (!auth()->user()->companies->contains($employee->team->company) || !auth()->user()->companies->contains($employee->role->company)) {
            return ResponseFormatter::error(['employee' => ['You do not own this employee']], 'You do not own this employee', 403);
        }

        return ResponseFormatter::success($employee, 'Successfully fetched the employee');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        if (!auth()->user()->companies->contains($employee->team->company) || !auth()->user()->companies->contains($employee->role->company)) {
            return ResponseFormatter::error(['employee' => ['You do not own this employee']], 'You do not own this employee', 403);
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo')->store('assets/photos');
                $data['photo'] = $photo;

                Storage::delete($employee->photo);
            } else {
                $data['photo'] = $employee->photo;
            }

            $employee->update($data);

            DB::commit();

            return ResponseFormatter::success($employee, 'Successfully updated the employee');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($photo)) {
                Storage::delete($photo);
            }

            return ResponseFormatter::error(['errors' => [$e->getMessage()]], $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        if (!auth()->user()->companies->contains($employee->team->company) || !auth()->user()->companies->contains($employee->role->company)) {
            return ResponseFormatter::error(['employee' => ['You do not own this employee']], 'You do not own this employee', 403);
        }

        $employee->delete();

        return ResponseFormatter::success(message: 'Successfully deleted the employee', code: 204);
    }
}
