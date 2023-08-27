<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Http\Requests\API\Team\StoreTeamRequest;
use App\Http\Requests\API\Team\UpdateTeamRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $name = $request->query('name');

        $teams = Team::query()
            ->withCount('employees')
            ->whereHas('company', function ($query) {
                return $query->whereHas('users', function ($query) {
                    return $query->whereUserId(auth()->id());
                });
            })
            ->when($name, function ($query) use ($name) {
                return $query->where('name', 'like', "%{$name}%");
            })
            ->paginate($limit);

        return ResponseFormatter::success($teams, 'Successfully fetched all the teams');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->hasFile('icon')) {
                $icon = $request->file('icon')->store('assets/icons');
                $data['icon'] = $icon;
            }

            $team = Team::create($data);

            DB::commit();

            return ResponseFormatter::success($team, 'Successfully created a new team', 201);
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
    public function show(Team $team)
    {
        if (!auth()->user()->companies->contains($team->company)) {
            return ResponseFormatter::error(['team' => ['You do not own this team']], 'You do not own this team', 403);
        }

        return ResponseFormatter::success($team, 'Successfully fetched the team');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        if (!auth()->user()->companies->contains($team->company)) {
            return ResponseFormatter::error(['team' => ['You do not own this team']], 'You do not own this team', 403);
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->hasFile('icon')) {
                $icon = $request->file('icon')->store('assets/icons');
                $data['icon'] = $icon;

                Storage::delete($team->icon);
            } else {
                $data['icon'] = $team->icon;
            }

            $team->update($data);

            DB::commit();

            return ResponseFormatter::success($team, 'Successfully updated the team');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($icon)) {
                Storage::delete($icon);
            }

            return ResponseFormatter::error(['errors' => [$e->getMessage()]], $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        if (!auth()->user()->companies->contains($team->company)) {
            return ResponseFormatter::error(['team' => ['You do not own this team']], 'You do not own this team', 403);
        }

        $team->delete();

        return ResponseFormatter::success(message: 'Successfully deleted the team', code: 204);
    }

    public function all(Request $request): JsonResponse
    {
        $teams = Team::query()
            ->whereHas('company.users', function ($query) {
                return $query->whereUserId(auth()->id());
            })
            ->get();

        return ResponseFormatter::success($teams, 'Successfully fetched all the teams');
    }

    public function getAllTeamsBasedOnCompanyId(Request $request, Company $company): JsonResponse
    {
        if (!auth()->user()->companies->contains($company->id)) {
            return ResponseFormatter::error(['team' => ['You do not own this team']], 'You do not own this team', 403);
        }

        $teams = Team::withCount('employees')->whereCompanyId($company->id)
            ->get();

        return ResponseFormatter::success($teams, 'Successfully fetched all the teams');
    }
}
