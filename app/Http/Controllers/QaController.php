<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Jobs\ProcessQuestion;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class QaController extends Controller
{
    /**
     * Dispatches a job to process a question and returns a query ID.
     */
    public function ask(Request $request)
    {
        $question = $request->input('question');

        if (!$question) {
            return response()->json(['error' => 'No question provided'], 400);
        }

        // Generate a unique key for this query
        $cacheKey = 'query_' . Str::uuid();

        // Dispatch the job to the queue
        ProcessQuestion::dispatch($question, $cacheKey);

        // Immediately return the cache key so the client can poll for the result
        return response()->json([
            'message' => 'Your question is being processed.',
            'query_id' => $cacheKey
        ]);
    }

    /**
     * Checks the status of a processed question by its query ID.
     */
    public function check(Request $request)
    {
        $queryId = $request->query('query_id');

        if (!$queryId) {
            return response()->json(['error' => 'No query ID provided'], 400);
        }

        // Use Cache::pull() to atomically retrieve the item and remove it.
        // This prevents a race condition where one request reads the cache
        // while a second request removes it before the first one can.
        $result = Cache::pull($queryId);

        if ($result) {
            // Result is available, decode and return it.
            return response()->json(json_decode($result, true));
        }

        // Result is not yet available
        return response()->json(['status' => 'processing']);
    }
}
