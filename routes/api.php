<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QaController;

Route::post('/auth/register', [QaController::class, 'register']);
Route::post('/auth/login', [QaController::class, 'login']);
Route::post('/auth/logout', [QaController::class, 'logout']);
Route::get('/auth/me', [QaController::class, 'me']);

Route::post('/chats', [QaController::class, 'createChat']);
Route::get('/chats', [QaController::class, 'getChats']);
Route::get('/analytics', [QaController::class, 'analytics']);
Route::get('/chats/{chatId}', [QaController::class, 'getChat']);
Route::get('/chats/{chatId}/export', [QaController::class, 'exportChat']);
Route::patch('/chats/{chatId}/title', [QaController::class, 'updateChatTitle']);
Route::delete('/chats/{chatId}', [QaController::class, 'deleteChat']);

Route::post('/chats/{chatId}/upload', [QaController::class, 'uploadFile']);
Route::delete('/chats/{chatId}/files/{fileId}', [QaController::class, 'deleteFile']);
Route::get('/chats/{chatId}/files/content', [QaController::class, 'getFileContent']);
Route::get('/chats/{chatId}/files/{fileId}/download', [QaController::class, 'downloadFile']);

Route::post('/chats/{chatId}/ask', [QaController::class, 'ask']);
Route::patch('/chats/{chatId}/messages/{messageId}', [QaController::class, 'editMessage']);
Route::get('/check-status', [QaController::class, 'check']);
