<?php

namespace MyForksFiles\CliPack\Commands;

use App\Models\SecurityAuditReport;
use App\Services\SecurityAuditService;
use Illuminate\Console\Command;

class RunSecurityAuditCommand extends Command
{
    protected $signature = 'security:audit {--json : Print raw JSON output}';

    protected $description = 'Run server security audit and store the report';

    public function handle(SecurityAuditService $service): int
    {
        $report = $service->run();

        $saved = SecurityAuditReport::create($report);

        $this->info('Security audit completed.');
        $this->line('Report ID: '.$saved->id);
        $this->line('Risk score: '.$saved->risk_score);
        $this->line('PHP: '.$saved->php_version);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
