<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\ChatMessage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessQuestion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $question;
    protected string $cacheKey;
    protected int $chatId;
    protected string $uploadPath;
    protected int $messageId;

    public function __construct(string $question, string $cacheKey, int $chatId, string $uploadPath, int $messageId)
    {
        $this->question = $question;
        $this->cacheKey = $cacheKey;
        $this->chatId = $chatId;
        $this->uploadPath = $uploadPath;
        $this->messageId = $messageId;
    }

    public function handle(): void
    {
        $pythonExecutable = 'C:\\Users\\AmirFR\\AppData\\Local\\Programs\\Python\\Python312\\python.exe';
        $scriptPath = base_path('main.py');

        try {
            $command = [$pythonExecutable, $scriptPath, $this->question, (string)$this->chatId];

            $process = new Process($command);
            $process->setTimeout(300);
            $process->run();

            Log::info("Python STDOUT: " . $process->getOutput());
            Log::info("Python STDERR: " . $process->getErrorOutput());

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = trim($process->getOutput());

            if (empty($output)) {
                Log::error("Empty output from Python script");
                throw new \Exception("Python script returned no output");
            }

            $parsedOutput = $this->parseOutput($output);

            $message = ChatMessage::find($this->messageId);
            if ($message) {
                $message->update([
                    'answer' => $parsedOutput['answer'],
                    'citations' => $parsedOutput['citations']
                ]);
            }

            Cache::put(
                $this->cacheKey,
                json_encode([
                    'status' => 'completed',
                    'output' => $output,
                    'parsed' => $parsedOutput
                ]),
                now()->addMinutes(10)
            );

        } catch (ProcessFailedException $exception) {
            Log::error("Python process failed for query ID {$this->cacheKey}: " . $exception->getMessage(), [
                'output' => $exception->getProcess()->getOutput(),
                'error_output' => $exception->getProcess()->getErrorOutput()
            ]);

            Cache::put(
                $this->cacheKey,
                json_encode([
                    'status' => 'error',
                    'details' => 'Python script execution failed.',
                    'error_output' => $exception->getProcess()->getErrorOutput()
                ]),
                now()->addMinutes(5)
            );

        } catch (\Exception $exception) {
            Log::error("An unexpected error occurred for query ID {$this->cacheKey}: " . $exception->getMessage());
            Cache::put(
                $this->cacheKey,
                json_encode([
                    'status' => 'error',
                    'details' => 'An unexpected server error occurred.'
                ]),
                now()->addMinutes(5)
            );
        }
    }

    private function parseOutput($output)
    {
        $answer = '';
        $citations = [];

        Log::info("Raw Python output: " . $output);

        if (strpos($output, '--- Answer ---') !== false) {
            preg_match('/--- Answer ---\s*\n(.*?)(?=\n--- Citations ---|$)/s', $output, $answerMatch);
            if (isset($answerMatch[1])) {
                $answer = trim($answerMatch[1]);
            }

            preg_match('/--- Citations ---\s*\n(.*?)(?=\n--- Summary ---|$)/s', $output, $citationsMatch);
            if (isset($citationsMatch[1])) {
                $citationsStr = trim($citationsMatch[1]);
                $citationsStr = str_replace(['[', ']', "'", '"'], '', $citationsStr);
                $citationsArray = array_filter(array_map('trim', explode(',', $citationsStr)));
                $citations = array_values(array_unique($citationsArray));
            }
        } elseif (strpos($output, '--- Clarification ---') !== false) {
            preg_match('/--- Clarification ---\s*\n(.*)/s', $output, $clarificationMatch);
            if (isset($clarificationMatch[1])) {
                $answer = trim($clarificationMatch[1]);
            }
        } else {
            $answer = trim($output);
        }

        Log::info("Parsed answer: " . $answer);
        Log::info("Parsed citations: " . json_encode($citations));

        return [
            'answer' => $answer,
            'citations' => $citations
        ];
    }
}
