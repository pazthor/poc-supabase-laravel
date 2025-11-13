<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Register a new user with Supabase
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'full_name' => 'required|string|max:255',
            'role' => 'sometimes|in:manager,employee,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Sign up user with Supabase Auth
        $response = $this->supabase->signUp(
            $request->input('email'),
            $request->input('password'),
            [
                'full_name' => $request->input('full_name'),
                'role' => $request->input('role', 'employee'),
            ]
        );

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $response->json()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => $response->json()
        ], 201);
    }

    /**
     * Login user with Supabase
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Sign in with Supabase
        $response = $this->supabase->signIn(
            $request->input('email'),
            $request->input('password')
        );

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'error' => $response->json()
            ], 401);
        }

        $data = $response->json();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'access_token' => $data['access_token'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? null,
                'user' => $data['user'] ?? null,
            ]
        ]);
    }

    /**
     * Get current user profile from Supabase
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No token provided'
            ], 401);
        }

        $response = $this->supabase->getUser($token);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'error' => $response->json()
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $response->json()
        ]);
    }

    /**
     * Logout (invalidate token)
     * Note: Supabase handles token invalidation on the client side
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully. Clear tokens on client side.'
        ]);
    }
}
