<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\FlowController;
use App\Http\Controllers\CustomerController;
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
});
