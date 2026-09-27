<?php

namespace Tests\Feature;

use App\Jobs\ProcessQuestion;
use App\Models\Chat;
use App\Models\ChatFile;
use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QuestionPrerequisitesTest extends TestCase
{
    use RefreshDatabase;

    public function test_questions_without_documents_do_not_start_python_jobs(): void
    {
        Queue::fake();
        $chat = Chat::create(['title' => 'Empty chat']);

        $this->postJson("/api/chats/{$chat->id}/ask", ['question' => 'Hello'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Upload at least one document to this chat before asking a question.');

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_questions_with_uploaded_documents_are_queued(): void
    {
        Queue::fake();
        $chat = Chat::create(['title' => 'Document chat']);
        ChatFile::create([
            'chat_id' => $chat->id,
            'filename' => 'notes.txt',
            'original_name' => 'notes.txt',
            'file_path' => 'notes.txt',
        ]);

        $this->postJson("/api/chats/{$chat->id}/ask", ['question' => 'Summarize the notes'])
            ->assertOk()
            ->assertJsonStructure(['query_id', 'message_id']);

        Queue::assertPushed(ProcessQuestion::class, 1);
        $this->assertDatabaseCount('chat_messages', 1);
    }

    public function test_edit_without_documents_preserves_the_existing_answer(): void
    {
        Queue::fake();
        $chat = Chat::create(['title' => 'Files removed']);
        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'question' => 'Original question',
            'answer' => 'Original answer',
        ]);

        $this->patchJson("/api/chats/{$chat->id}/messages/{$message->id}", ['question' => 'New question'])
            ->assertStatus(422);

        Queue::assertNothingPushed();
        $this->assertSame('Original question', $message->fresh()->question);
        $this->assertSame('Original answer', $message->fresh()->answer);
    }
}
