<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        // Basic validation (Consider using Form Requests for more complex validation)
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->errors()], 
                422
            );
        }

        try {
            $user = $this->userService->createUser($validator->validated());

            // You might want to return a token here for authentication in a real app
            return response()->json(['message' => 'User registered successfully', 'user' => $user], 200);
        } catch (ValidationException $e) {
            // Catch validation errors from the service (like duplicate email)
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Generic error handling
            
            return response()->json(['message' => 'Registration failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all users.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $users = $this->userService->getAllUsers();
            return response()->json(['users' => $users], 200);
        } catch (\Exception $e) {
            return response()->json(
                ['message' => 'Failed to retrieve users', 'error' => $e->getMessage()], 
                500
            );
        }
    }
    
    /**
     * Get user by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
            
            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(
                ['message' => 'Failed to retrieve user', 'error' => $e->getMessage()],
                 500
                );
        }
    }

}