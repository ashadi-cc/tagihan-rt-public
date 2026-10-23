<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware(['auth:sanctum', 'abilities:admin:manage'])->group(function () {
    Route::post('/register-whatsapp-id', [App\Http\Controllers\Api\RegisterWhatsAppIdController::class, 'register']);
    Route::get('/push-messages', [App\Http\Controllers\Api\PushMessageController::class, 'index']);
    Route::post('/forgot-password', [App\Http\Controllers\Api\ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::get('/organization', [App\Http\Controllers\Api\OrganizationController::class, 'getOrganization']);
    Route::post('/bot', [App\Http\Controllers\Api\BotController::class, 'handle']);
    
    // AI Function Call Endpoints
    Route::prefix('ai')->group(function () {
        Route::get('/current-user', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getCurrentUser']);
        Route::get('/users', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getUsers']);
        Route::get('/bills', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getUserBills']);
        Route::get('/global-bills', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getGlobalUserBills']);
        Route::get('/payments', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getUserPayments']);
        Route::get('/global-payments', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getGlobalUserPayments']);
        Route::get('/transactions', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getTransactions']);
        Route::get('/inventory', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getInventoryItems']);
        Route::get('/events', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getEvents']);
        Route::get('/tenants', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getTenants']);
        Route::get('/user-details', [App\Http\Controllers\Api\AiCallFunctionController::class, 'getUserDetails']);
    });
});