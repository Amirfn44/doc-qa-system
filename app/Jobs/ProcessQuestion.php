<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessQuestion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $question;
    protected string $cacheKey;
    protected ?string $filePath;

    public function __construct(string $question, string $cacheKey, ?string $filePath)
    {
        $this->question = $question;
        $this->cacheKey = $cacheKey;
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        $pythonExecutable = 'C:\\Users\\AmirFR\\AppData\\Local\\Programs\\Python\\Python312\\python.exe';
        $scriptPath = base_path('main.py');

        try {
            $command = [$pythonExecutable, $scriptPath, $this->question];
            if ($this->filePath) {
                $command[] = $this->filePath;
            }

            $process = new Process($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = $process->getOutput();

            Cache::put($this->cacheKey, json_encode(['status' => 'completed', 'output' => $output]), now()->addMinutes(10));

        } catch (ProcessFailedException $exception) {
            Log::error("Python process failed for query ID {$this->cacheKey}: " . $exception->getMessage(), [
                'output' => $exception->getProcess()->getOutput(),
                'error_output' => $exception->getProcess()->getErrorOutput()
            ]);

            Cache::put($this->cacheKey, json_encode(['status' => 'error', 'details' => 'Python script execution failed.', 'error_output' => $exception->getProcess()->getErrorOutput()]), now()->addMinutes(5));

        } catch (\Exception $exception) {
            Log::error("An unexpected error occurred for query ID {$this->cacheKey}: " . $exception->getMessage());
            Cache::put($this->cacheKey, json_encode(['status' => 'error', 'details' => 'An unexpected server error occurred.']), now()->addMinutes(5));
        }
    }
}
