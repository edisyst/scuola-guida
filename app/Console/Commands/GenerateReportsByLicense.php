<?php

namespace App\Console\Commands;

use App\Models\LicenseType;
use App\Services\ReportingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateReportsByLicense extends Command
{
    protected $signature = 'reports:generate-by-license {period : monthly|quarterly} {--license-type=all}';

    protected $description = 'Generate reports segmented by license type';

    public function __construct(private ReportingService $reporting) {
        parent::__construct();
    }

    public function handle(): int
    {
        $period = $this->argument('period');
        $licenseTypeOption = $this->option('license-type');

        // Determine period range
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        if ($period === 'monthly') {
            $from = now()->startOfMonth()->startOfDay();
            $to = now()->endOfMonth()->endOfDay();
        } elseif ($period === 'quarterly') {
            $from = now()->firstOfQuarter()->startOfDay();
            $to = now()->endOfQuarter()->endOfDay();
        } else {
            $this->error("Invalid period. Use 'monthly' or 'quarterly'.");
            return 1;
        }

        // Get license types to process
        $licenseTypes = [];

        if ($licenseTypeOption === 'all') {
            $licenseTypes = LicenseType::active()
                ->whereHas('quizzes', fn ($q) => $q->where('status', 'confirmed'))
                ->get();

            if ($licenseTypes->isEmpty()) {
                $this->warn('No active license types with confirmed quizzes found.');
                return 0;
            }
        } else {
            $lt = LicenseType::where('code', $licenseTypeOption)->first();
            if (!$lt) {
                $this->error("License type '{$licenseTypeOption}' not found.");
                return 1;
            }
            $licenseTypes = [$lt];
        }

        // Generate report for each license type
        foreach ($licenseTypes as $licenseType) {
            try {
                $report = $this->reporting->buildPeriodReport($from, $to, $licenseType);
                $this->info(
                    "✓ Report generated for {$licenseType->name} ({$licenseType->code}): " .
                    "{$report['total_attempts']} attempts, {$report['active_students']} active students"
                );
            } catch (\Exception $e) {
                $this->error("Failed to generate report for {$licenseType->name}: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }
}
