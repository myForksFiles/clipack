<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Class CleanUp
 *
 * @author myForksFiles(at)gmail.com
 *
 * @category CLI Laravel clear tools
 *
 *- -***
 */
class VideoYoutubeTranscriptArticle extends Command
{
    protected $signature = 'article:from-transcript

        {transcript : Path to TXT transcript file}

        {--dir=articles/youtube : Output directory inside storage/app}

        {--title= : Preferred article title}

        {--lang=pl : Article language}

        {--style=publicystyczny, konkretny, uporządkowany : Article style}

        {--model= : OpenAI model}

        {--dry-run : Print command without running it}';

    protected $description = 'Convert a TXT transcript into an article using a local ChatGPT/OpenAI CLI';

    public function handle(): int
    {
        $transcriptPath = $this->resolvePath((string) $this->argument('transcript'));

        if (! $transcriptPath || ! is_file($transcriptPath)) {
            $this->error(__('clipack::messages.transcript_file_not_found'));

            return self::FAILURE;
        }

        $dir = trim((string) $this->option('dir'), '/');
        $targetDir = storage_path('app/'.$dir);

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $baseName = pathinfo($transcriptPath, PATHINFO_FILENAME);
        $slug = Str::slug($this->option('title') ?: $baseName);
        $outputPath = $targetDir.DIRECTORY_SEPARATOR.$slug.'.md';
        $model = $this->option('model')
            ?: config('services.openai_article.model', 'gpt-5.5');

        $command = [
            config('services.openai_article.bin', 'python3'),
            config('services.openai_article.script', base_path('bin/chatgpt-article.py')),
            '--input',
            $transcriptPath,
            '--output',
            $outputPath,
            '--model',
            $model,
            '--language',
            (string) $this->option('lang'),
            '--style',
            (string) $this->option('style'),
        ];

        if ($title = $this->option('title')) {
            $command[] = '--title';
            $command[] = (string) $title;
        }

        if ($this->option('dry-run')) {
            $this->line($this->toShellCommand($command));

            return self::SUCCESS;
        }

        $this->info(__('clipack::messages.converting_transcript_to_article'));

        $result = Process::timeout(600)
            ->env([
                'OPENAI_API_KEY' => env('OPENAI_API_KEY'),
                'OPENAI_MODEL' => $model,
                'PATH' => env('PATH') ?: '/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin',
            ])
            ->run($command, function (string $type, string $output): void {
                $output = trim($output);

                if ($output === '') {
                    return;
                }

                $type === 'err'
                    ? $this->warn($output)
                    : $this->line($output);
            });

        if ($result->failed()) {
            $this->error(__('clipack::messages.article_generation_failed'));
            $this->line($result->errorOutput() ?: $result->output());

            return self::FAILURE;
        }

        $this->info(__('clipack::messages.done'));
        $this->line(__('clipack::messages.article_file', ['path' => $outputPath]));

        return self::SUCCESS;
    }

    private function resolvePath(string $path): ?string
    {
        if (is_file($path)) {
            return realpath($path) ?: $path;
        }

        $storagePath = storage_path('app/'.ltrim($path, '/'));

        if (is_file($storagePath)) {
            return realpath($storagePath) ?: $storagePath;
        }

        return null;
    }

    private function toShellCommand(array $command): string
    {
        return implode(' ', array_map(escapeshellarg(...), $command));
    }
}
