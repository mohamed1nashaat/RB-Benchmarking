<?php

namespace App\Console\Commands;

use App\Services\AnomalyDetectionService;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DetectAnomalies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anomalies:detect
                            {--scope=all : Scope: all, account, campaign}
                            {--scope-id= : Account or Campaign ID}
                            {--metric=spend : Metric to analyze}
                            {--method=combined : Detection method: zscore, iqr, percentage_change, seasonal, combined}
                            {--sensitivity=moderate : Sensitivity: low, moderate, high}
                            {--lookback=30 : Lookback days for historical comparison}
                            {--tenant-id=1 : Tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect anomalies in ad performance metrics';

    protected AnomalyDetectionService $anomalyService;

    /**
     * Create a new command instance.
     */
    public function __construct(AnomalyDetectionService $anomalyService)
    {
        parent::__construct();
        $this->anomalyService = $anomalyService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $scope = $this->option('scope');
        $scopeId = $this->option('scope-id');
        $metric = $this->option('metric');
        $method = $this->option('method');
        $sensitivity = $this->option('sensitivity');
        $lookback = (int) $this->option('lookback');
        $tenantId = (int) $this->option('tenant-id');

        $this->info("🔍 Detecting anomalies for {$metric}...");
        $this->info("Settings: {$method} method, {$sensitivity} sensitivity, {$lookback} days lookback");
        $this->newLine();

        // Build conditions
        $conditions = [
            'metric' => $metric,
            'detection_method' => $method,
            'sensitivity' => $sensitivity,
            'lookback_days' => $lookback,
            'scope' => $scope,
            'scope_id' => $scopeId,
        ];

        try {
            // Run anomaly detection
            $result = $this->anomalyService->detectAnomalies($conditions);

            // Display results
            $this->displayResults($result, $scope, $scopeId);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error detecting anomalies: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Display detection results
     */
    protected function displayResults(array $result, string $scope, ?string $scopeId): void
    {
        $metadata = $result['metadata'] ?? [];
        $analysis = $result['analysis'] ?? [];
        $anomalies = $result['anomalies'] ?? [];

        // Display metadata
        $this->info("📊 Analysis Summary:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Scope', ucfirst($scope) . ($scopeId ? " (ID: {$scopeId})" : '')],
                ['Current Value', $metadata['current_value'] ?? 'N/A'],
                ['Historical Data Points', $metadata['historical_data_points'] ?? 'N/A'],
                ['Detection Method', $metadata['method'] ?? 'N/A'],
                ['Sensitivity', $metadata['sensitivity'] ?? 'N/A'],
            ]
        );
        $this->newLine();

        // Display analysis details
        if (!empty($analysis)) {
            $this->info("📈 Statistical Analysis:");

            foreach ($analysis as $methodName => $methodData) {
                if (is_array($methodData)) {
                    $this->line("  <fg=cyan>{$methodName}:</>");
                    foreach ($methodData as $key => $value) {
                        if (is_array($value)) {
                            // Handle nested arrays (like expected_range)
                            $this->line("    • {$key}: " . json_encode($value));
                        } elseif (is_numeric($value)) {
                            $value = round($value, 2);
                            $this->line("    • {$key}: {$value}");
                        } else {
                            $this->line("    • {$key}: {$value}");
                        }
                    }
                }
            }
            $this->newLine();
        }

        // Display anomalies
        if ($result['detected'] && !empty($anomalies)) {
            $this->warn("⚠️  ANOMALIES DETECTED!");
            $this->newLine();

            foreach ($anomalies as $index => $anomaly) {
                $severity = $anomaly['severity'] ?? 'unknown';
                $type = $anomaly['type'] ?? 'unknown';
                $description = $anomaly['description'] ?? 'No description';

                $severityColor = match($severity) {
                    'extreme' => 'red',
                    'high' => 'yellow',
                    'moderate' => 'yellow',
                    'mild' => 'cyan',
                    default => 'white',
                };

                $this->line("  Anomaly #" . ($index + 1));
                $this->line("  <fg={$severityColor}>  • Severity: " . strtoupper($severity) . "</>");
                $this->line("    • Type: {$type}");
                $this->line("    • Description: {$description}");

                // Display additional anomaly details
                foreach ($anomaly as $key => $value) {
                    if (!in_array($key, ['severity', 'type', 'description']) && !is_array($value)) {
                        $this->line("    • {$key}: {$value}");
                    }
                }
                $this->newLine();
            }

            // Get suggestions
            $suggestions = $this->anomalyService->getAnomalySuggestions($anomalies, $metadata['metric'] ?? '');
            if (!empty($suggestions)) {
                $this->info("💡 Suggestions:");
                foreach ($suggestions as $suggestion) {
                    $this->line("  • {$suggestion}");
                }
                $this->newLine();
            }
        } else {
            $this->info("✅ No anomalies detected. Performance is within normal range.");
            $this->newLine();
        }
    }
}
