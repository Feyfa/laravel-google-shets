<?php

use App\Http\Controllers\SheetsController;
use App\Http\Controllers\SheetsController2;
use App\Http\Controllers\SheetsController3;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');

Route::prefix('v1')->group(function () {
    Route::get('/sheets/read', [SheetsController::class, 'read']);
    Route::get('/sheets/update', [SheetsController::class, 'update']);
    Route::get('/sheets/create', [SheetsController::class, 'create']);
    Route::get('/sheets/delete', [SheetsController::class, 'delete']);
});

Route::get('/connect-google', [SheetsController2::class, 'connectGoogleAccount'])->name('connect.google');
Route::get('/revokegoogletoken', [SheetsController2::class, 'revokeGoogleToken'])->name('revokegoogletoken');
Route::get('/store-google-token', [SheetsController2::class, 'storeGoogleToken']);
Route::post('/export-students', [SheetsController2::class, 'exportDataToGoogleSheets']);

Route::prefix('v2')->group(function () {
    Route::get('/sheet/create', [SheetsController3::class, 'create']);
    Route::get('/sheet/add-header', [SheetsController3::class, 'addHeader']);
    Route::get('/sheet/add-data', [SheetsController3::class, 'addData']);
    Route::get('/sheet/test', [SheetsController3::class, 'jidantest']);
});
