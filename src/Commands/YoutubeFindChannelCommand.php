<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use MyForksFiles\CliPack\Services\YoutubeChannelResolverService;
use Throwable;

class YoutubeFindChannelCommand extends Command
{
    protected $signature = 'mff:youtube:channel-id
                            {value? : Handle, URL, or search phrase}
                            {--handle= : YouTube handle, e.g. @GoogleDevelopers}
                            {--url= : YouTube channel URL}
                            {--query= : Search phrase for a channel}';

    protected $description = 'Resolve a YouTube channel ID from a handle, URL, or search phrase';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['youtube:channel-id'];

    public function handle(YoutubeChannelResolverService $resolver): int
    {
        $input = $this->resolveInput();

        if ($input === null) {
            $this->warn('Provide a handle, URL, or query.');
            $this->line('Examples:');
            $this->line('php artisan mff:youtube:channel-id --handle=@GoogleDevelopers');
            $this->line('php artisan mff:youtube:channel-id --url=https://www.youtube.com/@GoogleDevelopers');
            $this->line('php artisan mff:youtube:channel-id --query="Google Developers"');
            $this->line('php artisan mff:youtube:channel-id @GoogleDevelopers');

            return self::INVALID;
        }

        try {
            $result = $resolver->resolve($input);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Channel resolved.');
        $this->table(
            ['Field', 'Value'],
            [
                ['input', (string) ($result['input'] ?? $input)],
                ['title', (string) ($result['title'] ?? '')],
                ['channel_id', (string) ($result['channel_id'] ?? '')],
                ['url', (string) ($result['url'] ?? '')],
                ['canonical_url', (string) ($result['canonical_url'] ?? '')],
                ['resolved_by', (string) ($result['resolved_by'] ?? '')],
            ]
        );

        return self::SUCCESS;
    }

    private function resolveInput(): ?string
    {
        $value = $this->argument('value');
        $handle = $this->option('handle');
        $url = $this->option('url');
        $query = $this->option('query');

        if (is_string($handle) && $handle !== '') {
            return $handle;
        }

        if (is_string($url) && $url !== '') {
            return $url;
        }

        if (is_string($query) && $query !== '') {
            return $query;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', 'www.youtube.com', 'youtube.com'])) {
            return $value;
        }

        if (Str::startsWith($value, '@')) {
            return $value;
        }

        return $value;
    }
}
