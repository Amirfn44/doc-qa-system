<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QueryStatusTest extends TestCase
{
    public function test_answer_status_survives_repeated_checks(): void
    {
        Cache::put('query_reconnect_test', json_encode([
            'status' => 'completed',
            'parsed' => ['answer' => 'The sample folder is green.', 'citations' => []],
        ]), 60);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->getJson('/api/check-status?query_id=query_reconnect_test')
                ->assertOk()
                ->assertJsonPath('status', 'completed')
                ->assertJsonPath('parsed.answer', 'The sample folder is green.');
        }
    }

    public function test_failure_status_survives_repeated_checks(): void
    {
        Cache::put('query_failed_test', json_encode([
            'status' => 'error', 'details' => 'Please try again.',
        ]), 60);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->getJson('/api/check-status?query_id=query_failed_test')
                ->assertOk()
                ->assertJsonPath('status', 'error');
        }
    }
}
