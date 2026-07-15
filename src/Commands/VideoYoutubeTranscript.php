<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use MyForksFiles\CliPack\Services\TranscriptCleaner;
use Symfony\Component\Finder\Finder;

/**
 * Class CleanUp
 *
 * @author myForksFiles(at)gmail.com
 *
 * @category CLI Laravel clear tools
 *
 *- -***
 */
class VideoYoutubeTranscript extends Command
{
    protected $signature = 'mff:youtube:transcript
        {url : YouTube video URL}
        {--dir=transcripts/youtube : Directory inside storage/app}
        {--lang=pl,en : Subtitle languages, e.g. pl,en,de}
        {--filename= : Custom filename without extension}
        {--browser= : Load cookies from browser, e.g. chrome, firefox, brave, safari}
        {--cookies= : Path to cookies.txt file}
        {--no-auto : Do not download automatic subtitles}
        {--dry-run : Print command without downloading}';

    protected $description = 'Download YouTube subtitles/transcript and save a clean TXT file';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['youtube:transcript'];

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

        $baseName = $this->option('filename')
            ? Str::slug((string) $this->option('filename'))
            : '%(id)s_%(title).100s';

        $outputTemplate = $targetDir.DIRECTORY_SEPARATOR.$baseName.'.%(ext)s';
        $command = [
            config('services.yt_dlp.bin', 'yt-dlp'),
            '--skip-download',
            '--write-subs',
            '--sub-langs',
            (string) $this->option('lang'),
            '--sub-format',
            'vtt/srt/best',
            '--output',
            $outputTemplate,
        ];

        if (! $this->option('no-auto')) {
            $command[] = '--write-auto-subs';
        }

        $this->appendCookiesOptions($command);
        $command[] = $url;

        if ($this->option('dry-run')) {
            $this->line($this->toShellCommand($command));

            return self::SUCCESS;
        }

        $this->info(__('clipack::messages.downloading_youtube_transcript'));
        $before = $this->listSubtitleFiles($targetDir);

        $result = Process::timeout(300)
            ->env([
                'PATH' => (string) (getenv('PATH') ?: '/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin'),
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
            $this->error(__('clipack::messages.youtube_transcript_download_failed'));
            $this->line($result->errorOutput() ?: $result->output());

            return self::FAILURE;
        }

        $after = $this->listSubtitleFiles($targetDir);
        $newFiles = array_values(array_diff($after, $before));

        if (empty($newFiles)) {
            $this->error(__('clipack::messages.downloaded_subtitles_not_found'));
            $this->warn(__('clipack::messages.video_may_have_no_subtitles_or_needs_cookies'));
            $this->line(__('clipack::messages.try_youtube_transcript_with_browser'));

            return self::FAILURE;
        }

        $subtitleFile = $newFiles[0];
        $raw = file_get_contents($subtitleFile);

        if ($raw === false) {
            $this->error(__('clipack::messages.cannot_read_subtitle_file', ['path' => $subtitleFile]));

            return self::FAILURE;
        }

        $clean = TranscriptCleaner::clean($raw);
        $txtFile = preg_replace('/\.(vtt|srt)$/i', '.txt', $subtitleFile) ?: ($subtitleFile.'.txt');
        file_put_contents($txtFile, $clean);

        $this->info(__('clipack::messages.done'));
        $this->line(__('clipack::messages.subtitles_file', ['path' => $subtitleFile]));
        $this->line(__('clipack::messages.txt_file', ['path' => $txtFile]));

        return self::SUCCESS;
    }

    private function listSubtitleFiles(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $finder = new Finder;
        $finder->files()
            ->in($dir)
            ->name('/\.(vtt|srt)$/i');

        $files = [];

        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        sort($files);

        return $files;
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
