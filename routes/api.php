<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QaController;

Route::post('/chats', [QaController::class, 'createChat']);
Route::get('/chats', [QaController::class, 'getChats']);
Route::get('/chats/{chatId}', [QaController::class, 'getChat']);
Route::patch('/chats/{chatId}/title', [QaController::class, 'updateChatTitle']);
Route::delete('/chats/{chatId}', [QaController::class, 'deleteChat']);

Route::post('/chats/{chatId}/upload', [QaController::class, 'uploadFile']);
Route::delete('/chats/{chatId}/files/{fileId}', [QaController::class, 'deleteFile']);
Route::get('/chats/{chatId}/files/content', [QaController::class, 'getFileContent']);

Route::post('/chats/{chatId}/ask', [QaController::class, 'ask']);
Route::patch('/chats/{chatId}/messages/{messageId}', [QaController::class, 'editMessage']);
Route::get('/check-status', [QaController::class, 'check']);
