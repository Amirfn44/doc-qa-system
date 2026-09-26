<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_chat_can_be_created(): void
    {
        $response = $this->postJson('/api/chats', ['title' => 'Research']);

        $response->assertCreated()
            ->assertJsonPath('title', 'Research');
    }

    public function test_upload_rejects_unsupported_file_types(): void
    {
        $chat = $this->postJson('/api/chats', ['title' => 'Research'])
            ->assertCreated()
            ->json('chat_id');

        $response = $this->post('/api/chats/' . $chat . '/upload', [
            'file' => UploadedFile::fake()->create('malware.exe', 10),
        ]);

        $response->assertStatus(422);
    }

    public function test_text_file_can_be_downloaded(): void
    {
        $chat = $this->postJson('/api/chats', ['title' => 'Research'])
            ->assertCreated()
            ->json('chat_id');

        $upload = $this->post('/api/chats/' . $chat . '/upload', [
            'file' => UploadedFile::fake()->createWithContent('notes.txt', 'important notes'),
        ]);

        $upload->assertOk();
        $fileId = \App\Models\ChatFile::firstOrFail()->id;

        $this->get('/api/chats/' . $chat . '/files/' . $fileId . '/download')
            ->assertOk()
            ->assertHeader('content-disposition');
    }
}
