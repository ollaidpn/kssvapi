<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogViewerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Log Viewer (page d'accueil)
Route::get('/', [LogViewerController::class, 'index']);
Route::post('/logs/clear', [LogViewerController::class, 'clear'])->name('logs.clear');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
