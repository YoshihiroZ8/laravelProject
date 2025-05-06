<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HelloWorldController; 
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\UploadController;


Route::get('/hello', HelloWorldController::class);

/**
 * API Routes [User]
 */
Route::post('/register', [UserController::class, 'register']);
Route::get('/users', [UserController::class, 'index']); // Get all users
Route::get('/users/{id}', [UserController::class, 'show']); // Get user by ID

Route::post('/uploads', [UploadController::class, 'store']);

//routes for upload status and history
Route::get('/uploads', [UploadController::class, 'index']); // Get upload history
Route::get('/uploads/{upload}', [UploadController::class, 'show']); // Get status of a specific upload