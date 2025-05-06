<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HelloWorldService; // Import the service
use Illuminate\Http\JsonResponse;

class HelloWorldController extends Controller
{
    protected HelloWorldService $helloWorldService;

    // Inject the service via the constructor
    public function __construct(HelloWorldService $helloWorldService)
    {
        $this->helloWorldService = $helloWorldService;
    }

    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $message = $this->helloWorldService->getMessage();

        return response()->json(['message' => $message]);
    }
}
