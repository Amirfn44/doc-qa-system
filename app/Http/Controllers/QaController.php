<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Jobs\ProcessQuestion;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\Chat;
use App\Models\ChatFile;
use App\Models\ChatMessage;

class QaController extends Controller
{
    public function createChat(Request $request)
    {
        $chat = Chat::create([
            'title' => $request->input('title', 'New Chat')
        ]);

        return response()->json([
            'chat_id' => $chat->id,
            'title' => $chat->title,
            'created_at' => $chat->created_at
        ]);
    }

    public function updateChatTitle(Request $request, $chatId)
    {
        $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $chat = Chat::findOrFail($chatId);
        $chat->update(['title' => $request->input('title')]);

        return response()->json([
            'message' => 'Chat title updated successfully',
            'title' => $chat->title
        ]);
    }

    public function getChats()
    {
        $chats = Chat::with('messages', 'files')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($chats);
    }

    public function getChat($chatId)
    {
        $chat = Chat::with(['messages' => function($query) {
            $query->orderBy('created_at', 'asc');
        }, 'files'])->findOrFail($chatId);

        return response()->json($chat);
    }

    public function deleteChat($chatId)
    {
        $chat = Chat::findOrFail($chatId);

        $uploadPath = base_path("data/uploads/{$chatId}");
        if (is_dir($uploadPath)) {
            $this->deleteDirectory($uploadPath);
        }

        $chat->delete();

        return response()->json(['message' => 'Chat deleted successfully']);
    }

    public function deleteFile($chatId, $fileId)
    {
        $file = ChatFile::where('chat_id', $chatId)->findOrFail($fileId);

        if (file_exists($file->file_path)) {
            unlink($file->file_path);
        }

        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }

    public function editMessage(Request $request, $chatId, $messageId)
    {
        $request->validate([
            'question' => 'required|string'
        ]);

        $message = ChatMessage::where('chat_id', $chatId)->findOrFail($messageId);

        $message->update([
            'question' => $request->input('question'),
            'answer' => null,
            'citations' => null
        ]);

        $cacheKey = 'query_' . Str::uuid();

        $uploadPath = base_path("data/uploads/{$chatId}");

        ProcessQuestion::dispatch($request->input('question'), $cacheKey, $chatId, $uploadPath, $message->id);

        $chat = Chat::findOrFail($chatId);
        $chat->touch();

        return response()->json([
            'message' => 'Question updated and reprocessing',
            'query_id' => $cacheKey,
            'message_id' => $message->id
        ]);
    }

    public function getFileContent(Request $request, $chatId)
    {
        $filename = $request->query('filename');

        if (!$filename) {
            return response()->json(['error' => 'Filename required'], 400);
        }

        $file = ChatFile::where('chat_id', $chatId)
                        ->where('original_name', $filename)
                        ->first();

        if (!$file) {
            return response()->json(['error' => 'File not found'], 404);
        }

        if (!file_exists($file->file_path)) {
            return response()->json(['error' => 'File not found on disk'], 404);
        }

        $content = file_get_contents($file->file_path);

        return response()->json([
            'filename' => $file->original_name,
            'content' => $content
        ]);
    }

    public function uploadFile(Request $request, $chatId)
    {
        $chat = Chat::findOrFail($chatId);

        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file provided'], 400);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $filename = time() . '_' . $originalName;

        $uploadPath = base_path("data/uploads/{$chatId}");
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $file->move($uploadPath, $filename);
        $filePath = $uploadPath . '/' . $filename;

        ChatFile::create([
            'chat_id' => $chatId,
            'filename' => $filename,
            'original_name' => $originalName,
            'file_path' => $filePath
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'filename' => $originalName
        ]);
    }

    public function ask(Request $request, $chatId)
    {
        $question = $request->input('question');

        if (!$question) {
            return response()->json(['error' => 'No question provided'], 400);
        }

        $chat = Chat::findOrFail($chatId);

        $message = ChatMessage::create([
            'chat_id' => $chatId,
            'question' => $question
        ]);

        $cacheKey = 'query_' . Str::uuid();

        $uploadPath = base_path("data/uploads/{$chatId}");

        ProcessQuestion::dispatch($question, $cacheKey, $chatId, $uploadPath, $message->id);

        $chat->touch();

        return response()->json([
            'message' => 'Your question is being processed.',
            'query_id' => $cacheKey,
            'message_id' => $message->id
        ]);
    }

    public function check(Request $request)
    {
        $queryId = $request->query('query_id');

        if (!$queryId) {
            return response()->json(['error' => 'No query ID provided'], 400);
        }

        $result = Cache::pull($queryId);

        if ($result) {
            return response()->json(json_decode($result, true));
        }

        return response()->json(['status' => 'processing']);
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
