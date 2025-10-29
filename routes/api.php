<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\FlowController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPipelineController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\DocumentController;
use App\Http\Middleware\DeviceSessionEnforcer;
use App\Http\Middleware\EnforcePasswordPolicy;
use App\Http\Middleware\RequestIdMiddleware;

Route::middleware([RequestIdMiddleware::class])->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware(['throttle:api', EnforcePasswordPolicy::class])
        ->name('auth.login');

    Route::post('/auth/2fa/verify', [AuthController::class, 'verify2fa'])
        ->middleware(['throttle:api'])
        ->name('auth.2fa.verify');

    Route::post('/auth/logout', [AuthController::class, 'logout'])
        ->middleware(['throttle:api', 'auth', DeviceSessionEnforcer::class])
        ->name('auth.logout');

    Route::post('/auth/refresh', [AuthController::class, 'refresh'])
        ->middleware(['throttle:api', 'auth', DeviceSessionEnforcer::class])
        ->name('auth.refresh');
});

// RBAC-protected sample resource routes
Route::middleware([RequestIdMiddleware::class, 'auth'])->group(function () {
    Route::get('/items', [ItemController::class, 'index'])->middleware('can:items.read');
    Route::post('/items', [ItemController::class, 'store'])->middleware('can:items.create');
    Route::put('/items/{item}', [ItemController::class, 'update'])->middleware('can:items.update');
    Route::delete('/items/{item}', [ItemController::class, 'destroy'])->middleware('can:items.delete');

    // ABAC example: sector-restricted report
    Route::get('/reports/sector/{setor}', function (string $setor) {
        // Pass ABAC attributes to Gate::authorize via second param
        Gate::authorize('reports.view', ['setor' => $setor]);
        return response()->json(['report' => 'ok', 'setor' => $setor]);
    })->middleware('can:reports.view');
});

// Sectors & Flows API
Route::middleware([RequestIdMiddleware::class])->group(function () {
    // Sectors
    Route::get('/sectors', [SectorController::class, 'index']);
    Route::post('/sectors', [SectorController::class, 'store']);

    // Flows
    Route::get('/flows', [FlowController::class, 'index']);
    Route::post('/flows', [FlowController::class, 'store']);
    Route::post('/flows/{id}/design', [FlowController::class, 'design']);
    Route::post('/flows/{id}/publish', [FlowController::class, 'publish']);

    // Customers
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::post('/customers/{id}/transition', [CustomerPipelineController::class, 'transition']);

    // Catalog & Subscriptions
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/plans', [PlanController::class, 'index']);
    Route::post('/plans', [PlanController::class, 'store']);
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::post('/tasks/{id}/assign', [TaskController::class, 'assign']);
    Route::post('/tasks/{id}/complete', [TaskController::class, 'complete']);
    Route::get('/tasks/kanban', [TaskController::class, 'kanban']);
    Route::get('/tasks/my-agenda', [TaskController::class, 'myAgenda']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/subscription', [NotificationController::class, 'saveSubscription']);

    // Files (S3-compatible)
    Route::get('/files', [FileController::class, 'index']);
    Route::post('/files/presign', [FileController::class, 'presign']);
    Route::post('/files/upload', [FileController::class, 'upload']);

    // Documents & Versions
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{id}', [DocumentController::class, 'show']);
    Route::put('/documents/{id}', [DocumentController::class, 'update']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);
    Route::post('/documents/{id}/autosave', [DocumentController::class, 'autosave']);
    Route::get('/documents/{id}/versions', [DocumentController::class, 'versions']);
    Route::post('/documents/{id}/versions/{version}/rollback', [DocumentController::class, 'rollback']);
});
