<?php

use App\Http\Controllers\SheetsController;
use App\Http\Controllers\SheetsController2;
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

Route::get('/sheets/read', [SheetsController::class, 'read']);
Route::get('/sheets/update', [SheetsController::class, 'update']);
Route::get('/sheets/create', [SheetsController::class, 'create']);
Route::get('/sheets/delete', [SheetsController::class, 'delete']);

Route::get('/connect-google', [SheetsController2::class, 'connectGoogleAccount'])->name('connect.google');
Route::get('/revokegoogletoken', [SheetsController2::class, 'revokeGoogleToken'])->name('revokegoogletoken');
Route::get('/store-google-token', [SheetsController2::class, 'storeGoogleToken']);
Route::post('/export-students', [SheetsController2::class, 'exportDataToGoogleSheets']);
