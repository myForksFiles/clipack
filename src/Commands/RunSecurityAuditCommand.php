<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use MyForksFiles\CliPack\Services\SecurityAuditService;

class RunSecurityAuditCommand extends Command
{
    protected $signature = 'mff:security:audit {--json : Print raw JSON output}';

    protected $description = 'Run a server security audit and print the report';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['security:audit'];

    public function handle(SecurityAuditService $service): int
    {
        $report = $service->run();

        $this->info('Security audit completed.');
        $this->line('Hostname: '.(string) ($report['hostname'] ?? 'unknown'));
        $this->line('Risk score: '.(string) ($report['risk_score'] ?? 0));
        $this->line('PHP: '.(string) ($report['php_version'] ?? PHP_VERSION));

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $summary = $report['summary_checks'] ?? [];
            if (is_array($summary) && $summary !== []) {
                $rows = [];
                foreach ($summary as $check => $passed) {
                    $rows[] = [(string) $check, $passed ? 'OK' : 'FAIL'];
                }
                $this->table(['Check', 'Status'], $rows);
            }
        }

        return self::SUCCESS;
    }
}
