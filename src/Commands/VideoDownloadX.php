<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Class CleanUp
 *
 * @package MyForksFiles\CliPack\Commands
 * @author myForksFiles(at)gmail.com
 * @category CLI Laravel clear tools
 *
 *- -***
 */
class VideoDownloadX extends Command
{
    protected $signature = 'video:download:x

        {url : X.com or Twitter post URL}

        {--cookies= : Path to cookies.txt file}

        {--browser= : Load cookies from browser, e.g. chrome, firefox, brave, safari}

        {--dir=videos/x : Directory inside storage/app}

        {--filename= : Custom filename without extension}

        {--quality=best : yt-dlp format, e.g. best, bv*+ba/b, mp4}

        {--dry-run : Print command without downloading}';

    protected $description = 'Download video from an X.com/Twitter post using yt-dlp';

    public function handle(): int
    {
        $url = trim((string) $this->argument('url'));

        if (! $this->isValidXUrl($url)) {
            $this->error(__('clipack::messages.invalid_x_url'));

            return self::FAILURE;
        }

        $url = $this->normalizeUrl($url);
        $dir = trim((string) $this->option('dir'), '/');
        $storagePath = storage_path('app/'.$dir);

        if (! is_dir($storagePath) && ! mkdir($storagePath, 0755, true) && ! is_dir($storagePath)) {
            $this->error(__('clipack::messages.cannot_create_directory', ['path' => $storagePath]));

            return self::FAILURE;
        }

        $filename = $this->option('filename')
            ? Str::slug((string) $this->option('filename'))
            : '%(uploader_id)s_%(id)s_%(title).80s';

        $outputTemplate = $storagePath.DIRECTORY_SEPARATOR.$filename.'.%(ext)s';
        $command = [
            'yt-dlp',
            '--no-playlist',
            '--restrict-filenames',
            '--merge-output-format',
            'mp4',
            '--format',
            (string) $this->option('quality'),
            '--output',
            $outputTemplate,
            '--print',
            'after_move:filepath',
        ];

        if ($browser = $this->option('browser')) {
            $command[] = '--cookies-from-browser';
            $command[] = (string) $browser;
        }

        if ($cookies = $this->option('cookies')) {
            $cookiesPath = realpath((string) $cookies);

            if (! $cookiesPath || ! is_file($cookiesPath)) {
                $this->error(__('clipack::messages.cookies_file_not_found', ['path' => $cookies]));

                return self::FAILURE;
            }

            $command[] = '--cookies';
            $command[] = $cookiesPath;
        }

        $command[] = $url;

        if ($this->option('dry-run')) {
            $this->line($this->toShellCommand($command));

            return self::SUCCESS;
        }

        $this->info(__('clipack::messages.downloading_x_video'));
        $this->line($url);

        $result = Process::timeout(300)
            ->env([
                'PATH' => env('PATH') ?: '/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin',
            ])
            ->run($command, function (string $type, string $output): void {
                $output = trim($output);

                if ($output === '') {
                    return;
                }

                if ($type === 'err') {
                    $this->warn($output);
                } else {
                    $this->line($output);
                }
            });

        if ($result->failed()) {
            $this->error(__('clipack::messages.download_failed'));
            $this->line($result->errorOutput() ?: $result->output());
            $this->newLine();
            $this->warn(__('clipack::messages.try_with_cookies'));
            $this->line('php artisan x:download-video "URL" --browser=chrome');
            $this->line(__('clipack::messages.or'));
            $this->line('php artisan x:download-video "URL" --cookies=/path/to/cookies.txt');

            return self::FAILURE;
        }

        $this->info(__('clipack::messages.done'));
        $this->line(__('clipack::messages.directory', ['path' => $storagePath]));

        return self::SUCCESS;
    }

    private function isValidXUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return false;
        }

        $host = strtolower($host);

        return in_array($host, [
            'x.com',
            'www.x.com',
            'twitter.com',
            'www.twitter.com',
            'mobile.twitter.com',
        ], true);
    }

    private function normalizeUrl(string $url): string
    {
        // yt-dlp usually supports x.com, but twitter.com can be more stable.
        return str_replace(
            ['https://x.com/', 'https://www.x.com/'],
            ['https://twitter.com/', 'https://twitter.com/'],
            $url
        );
    }

    private function toShellCommand(array $command): string
    {
        return implode(' ', array_map(escapeshellarg(...), $command));
    }
}
