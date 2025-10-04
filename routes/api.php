<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QaController;

Route::post('/chats', [QaController::class, 'createChat']);
Route::get('/chats', [QaController::class, 'getChats']);
Route::get('/chats/{chatId}', [QaController::class, 'getChat']);
Route::delete('/chats/{chatId}', [QaController::class, 'deleteChat']);

Route::post('/chats/{chatId}/upload', [QaController::class, 'uploadFile']);

Route::post('/chats/{chatId}/ask', [QaController::class, 'ask']);
Route::get('/check-status', [QaController::class, 'check']);
