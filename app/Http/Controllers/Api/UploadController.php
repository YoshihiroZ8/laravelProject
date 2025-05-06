<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Upload; // Import the Upload model
use App\Jobs\ProcessCsvUpload; // Import the Job
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse; // For type hinting

class UploadController extends Controller
{
    /**
     * Store a newly uploaded CSV file and dispatch processing job.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:102400', // Max 100MB, adjust as needed
        ]);

        $file             = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        // Generate a unique path within the 'uploads' directory
        $path             = $file->storeAs(
            'uploads',
            Str::uuid() . '.' . $file->getClientOriginalExtension()
            );

        // Create an Upload record
        $upload = Upload::create([
            'original_filename' => $originalFilename,
            'filepath'          => $path,
            'status'            => 'pending',
        ]);

        // Dispatch the job
        ProcessCsvUpload::dispatch($upload);

        return response()->json([
            'message'   => 'File uploaded successfully. Processing started.',
            'upload_id' => $upload->id,
            'status'    => $upload->status,
        ], 201);
    }

    /**
     * Display a listing of the uploads.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Retrieve uploads, ordered by latest, paginated
        $uploads = Upload::latest()->paginate(15); // Adjust pagination size as needed

        return response()->json($uploads);
    }

    /**
     * Display the specified upload status.
     *
     * @param Upload $upload // Route model binding
     * @return JsonResponse
     */
    public function show(Upload $upload): JsonResponse
    {
        // The $upload model is automatically fetched by Laravel's route model binding
        return response()->json($upload);
    }
}