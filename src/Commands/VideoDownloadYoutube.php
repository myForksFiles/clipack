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
class VideoDownloadYoutube extends Command
{
    protected $signature = 'video:download:yt

        {url : YouTube video URL}

        {--dir=videos/youtube : Directory inside storage/app}

        {--filename= : Custom filename without extension}

        {--quality=bv*+ba/b : yt-dlp format}

        {--browser= : Load cookies from browser, e.g. chrome, firefox, brave, safari}

        {--cookies= : Path to cookies.txt file}

        {--dry-run : Print command without downloading}';

    protected $description = 'Download video from YouTube using yt-dlp';

    public function handle(): int
    {
        $url = trim((string) $this->argument('url'));

        if (! $this->isValidYoutubeUrl($url)) {
            $this->error(__('clipack::messages.invalid_youtube_url'));

            return self::FAILURE;
        }

        $dir = trim((string) $this->option('dir'), '/');
        $targetDir = storage_path('app/'.$dir);

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = $this->option('filename')
            ? Str::slug((string) $this->option('filename'))
            : '%(uploader_id)s_%(id)s_%(title).100s';

        $output = $targetDir.DIRECTORY_SEPARATOR.$filename.'.%(ext)s';
        $command = [
            config('services.yt_dlp.bin', 'yt-dlp'),
            '--no-playlist',
            '--restrict-filenames',
            '--merge-output-format',
            'mp4',
            '--format',
            (string) $this->option('quality'),
            '--output',
            $output,
            '--print',
            'after_move:filepath',
        ];

        $this->appendCookiesOptions($command);
        $command[] = $url;

        if ($this->option('dry-run')) {
            $this->line($this->toShellCommand($command));

            return self::SUCCESS;
        }

        $this->info(__('clipack::messages.downloading_youtube_video'));

        $result = Process::timeout(900)
            ->env([
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
            $this->error(__('clipack::messages.youtube_video_download_failed'));
            $this->line($result->errorOutput() ?: $result->output());

            return self::FAILURE;
        }

        $this->info(__('clipack::messages.done'));
        $this->line(__('clipack::messages.directory', ['path' => $targetDir]));

        return self::SUCCESS;
    }

    private function isValidYoutubeUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, [
            'youtube.com',
            'www.youtube.com',
            'm.youtube.com',
            'youtu.be',
            'www.youtu.be',
        ], true);
    }

    private function appendCookiesOptions(array &$command): void
    {
        if ($browser = $this->option('browser')) {
            $command[] = '--cookies-from-browser';
            $command[] = (string) $browser;
        }

        if ($cookies = $this->option('cookies')) {
            $cookiesPath = realpath((string) $cookies);

            if ($cookiesPath) {
                $command[] = '--cookies';
                $command[] = $cookiesPath;
            }
        }
    }

    private function toShellCommand(array $command): string
    {
        return implode(' ', array_map(escapeshellarg(...), $command));
    }
}
