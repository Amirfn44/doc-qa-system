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

    public function __construct(string $question, string $cacheKey)
    {
        $this->question = $question;
        $this->cacheKey = $cacheKey;
    }

    public function handle(): void
    {
        // مسیر فایل اجرایی پایتون و اسکریپت
        $pythonExecutable = 'C:\\Users\\AmirFR\\AppData\\Local\\Programs\\Python\\Python312\\python.exe';
        $scriptPath = base_path('main.py');

        try {
            // آرگومان‌ها به صورت آرایه برای جلوگیری از تزریق دستور (command injection)
            $process = new Process([$pythonExecutable, $scriptPath, $this->question]);
            $process->run();

            // بررسی موفقیت‌آمیز بودن فرآیند
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // گرفتن خروجی از اسکریپت پایتون
            $output = $process->getOutput();

            // ذخیره نتیجه موفق در کش با زمان انقضا مناسب
            Cache::put($this->cacheKey, json_encode(['status' => 'completed', 'output' => $output]), now()->addMinutes(10));

        } catch (ProcessFailedException $exception) {
            // ثبت جزئیات خطا برای اشکال‌زدایی
            Log::error("Python process failed for query ID {$this->cacheKey}: " . $exception->getMessage(), [
                'output' => $exception->getProcess()->getOutput(),
                'error_output' => $exception->getProcess()->getErrorOutput()
            ]);

            // ذخیره پاسخ خطا در کش
            Cache::put($this->cacheKey, json_encode(['status' => 'error', 'details' => 'Python script execution failed.', 'error_output' => $exception->getProcess()->getErrorOutput()]), now()->addMinutes(5));

        } catch (\Exception $exception) {
             // مدیریت سایر خطاهای غیرمنتظره
            Log::error("An unexpected error occurred for query ID {$this->cacheKey}: " . $exception->getMessage());
            Cache::put($this->cacheKey, json_encode(['status' => 'error', 'details' => 'An unexpected server error occurred.']), now()->addMinutes(5));
        }
    }
}
