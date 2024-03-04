<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\UserController;
use \App\Http\Controllers\FileController;
use \App\Http\Controllers\RightController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Авторизация
Route::post('/authorization', [UserController::class, 'login']);
// Регистрация
Route::post('/registration', [UserController::class, 'signUp']);

// Для авторизованных пользователей
Route::middleware('auth:api')->group( function () {
    // Выход
    Route::get('/logout', [UserController::class, 'logout']);

    // Просмотр файлов пользователя
    Route::get('/files/disk', [FileController::class, 'owned']);
    // Просмотр файлов, к которым имеет доступ пользователь
    Route::get('/files/shared', [FileController::class, 'allowed']);

    // Скачивание файла
    Route::get('/files/{file_id}', [FileController::class, 'download']);
    // Загрузка файлов
    Route::post('/files', [FileController::class, 'store']);
    // Редактирование файла
    Route::patch('/files/{file_id}', [FileController::class, 'edit']);
    // Удаление файла
    Route::delete('/files/{file_id}', [FileController::class, 'destroy']);

    // Добавление прав доступа
    Route::post('/files/{file_id}/accesses', [RightController::class, 'add']);
    // Удаление прав доступа
    Route::delete('/files/{file_id}/accesses', [RightController::class, 'destroy']);


});
