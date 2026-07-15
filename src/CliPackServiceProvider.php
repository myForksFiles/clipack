<?php

namespace MyForksFiles\CliPack;

use Illuminate\Support\ServiceProvider;
use MyForksFiles\CliPack\Commands\ApacheLogs;
use MyForksFiles\CliPack\Commands\CheckAppSchema;
use MyForksFiles\CliPack\Commands\CleanAll;
use MyForksFiles\CliPack\Commands\CleanFiles;
use MyForksFiles\CliPack\Commands\CreateUser;
use MyForksFiles\CliPack\Commands\CrontabBackup;
use MyForksFiles\CliPack\Commands\DbDumper;
use MyForksFiles\CliPack\Commands\DbImporter;
use MyForksFiles\CliPack\Commands\DevLog;
use MyForksFiles\CliPack\Commands\DiskSpace;
use MyForksFiles\CliPack\Commands\ExportLang;
use MyForksFiles\CliPack\Commands\FreeSpace;
use MyForksFiles\CliPack\Commands\LogRotate;
use MyForksFiles\CliPack\Commands\RunPhp;
use MyForksFiles\CliPack\Commands\RunSecurityAuditCommand;
use MyForksFiles\CliPack\Commands\ScheduleList;
use MyForksFiles\CliPack\Commands\SetAuthBasic;
use MyForksFiles\CliPack\Commands\SymfonyLocalPhpSecurityChecker;
use MyForksFiles\CliPack\Commands\VideoDownloadX;
use MyForksFiles\CliPack\Commands\VideoDownloadYoutube;
use MyForksFiles\CliPack\Commands\VideoYoutubeTranscript;
use MyForksFiles\CliPack\Commands\VideoYoutubeTranscriptArticle;
use MyForksFiles\CliPack\Commands\YoutubeFindChannelCommand;
use MyForksFiles\CliPack\Services\SecurityAuditService;
use MyForksFiles\CliPack\Services\TranscriptCleaner;
use MyForksFiles\CliPack\Services\YoutubeChannelResolverService;

class CliPackServiceProvider extends ServiceProvider
{
    /**
     * @var array<int, class-string>
     */
    protected array $commands = [
        ApacheLogs::class,
        CheckAppSchema::class,
        CleanAll::class,
        CleanFiles::class,
        CreateUser::class,
        CrontabBackup::class,
        DbDumper::class,
        DbImporter::class,
        DevLog::class,
        DiskSpace::class,
        ExportLang::class,
        FreeSpace::class,
        LogRotate::class,
        RunPhp::class,
        RunSecurityAuditCommand::class,
        ScheduleList::class,
        SetAuthBasic::class,
        SymfonyLocalPhpSecurityChecker::class,
        VideoDownloadX::class,
        VideoDownloadYoutube::class,
        VideoYoutubeTranscript::class,
        VideoYoutubeTranscriptArticle::class,
        YoutubeFindChannelCommand::class,
    ];

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/app.php',
            'clipack'
        );

        $this->app->singleton(SecurityAuditService::class);
        $this->app->singleton(TranscriptCleaner::class);
        $this->app->singleton(YoutubeChannelResolverService::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/lang', 'clipack');

        $this->publishes([
            __DIR__.'/config/app.php' => config_path('clipack.php'),
        ], 'clipack-config');

        $this->publishes([
            __DIR__.'/lang' => $this->app->langPath('vendor/clipack'),
        ], 'clipack-lang');

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * @return array<int, class-string>
     */
    #[\Override]
    public function provides(): array
    {
        return array_merge(
            [self::class, SecurityAuditService::class, TranscriptCleaner::class, YoutubeChannelResolverService::class],
            $this->commands,
        );
    }
}
