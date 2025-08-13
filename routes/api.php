<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\NodeController;



Route::prefix('v1')->group(function (){
    Route::post('register', [AuthController::class, 'createUser']);
    Route::post('login', [AuthController::class,'login']);

    Route::middleware('auth:sanctum')->group(function(){
        Route::post('/nodes', [NodeController::class, 'createNode']);
        Route::get('/nodes/{id}/children', [NodeController::class,'getChildren']);
        Route::put('/nodes/{id}/change-parent', [NodeController::class, 'changeParent']);
    });
});