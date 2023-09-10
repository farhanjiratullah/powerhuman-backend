<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    // public function create(): View
    // {
    //     return view('auth.register');
    // }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company' => 'required',
            'company.name' => 'required|string|max:255',
            'company.logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            // Create the nested structure for the company errors
            $companyErrors = [];
            if ($errors->has('company.name')) {
                $companyErrors['name'] = $errors->get('company.name');
            }
            if ($errors->has('company.logo')) {
                $companyErrors['logo'] = $errors->get('company.logo');
            }

            // Create the final error response structure
            $responseErrors = [
                'name' => $errors->get('name'),
                'email' => $errors->get('email'),
                'password' => $errors->get('password'),
                'company' => $companyErrors,
            ];

            return response()->json([
                'meta' => [
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Request errors',
                ],
                'errors' => $responseErrors
            ], 422);
        }

        DB::beginTransaction();
        try {
            $validated = $validator->validated();

            $validated['password'] = Hash::make($validated['password']);

            $user = User::create($validated);

            if ($request->hasFile('company.logo')) {
                $logo = $request->file('company.logo')->store('assets/logos');
                $validated['company']['logo'] = $logo;
            }

            $company = $user->companies()->create($validated['company']);

            $token = $user->createToken("token")->plainTextToken;

            DB::commit();

            return ResponseFormatter::success([
                'user' => $user,
                'token' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.expiration') . " minutes"
                ]
            ], 'Successfully registered a new user');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($logo)) {
                Storage::delete($logo);
            }

            return ResponseFormatter::error(['errors' => [$e->getMessage()]], $e->getMessage(), 500);
        }

        // event(new Registered($user));
    }
}
