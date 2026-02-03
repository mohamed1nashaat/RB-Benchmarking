<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Integration;

class CsvImportController extends Controller
{
    /**
     * Upload and import historical Facebook CSV data
     */
    public function importFacebookCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:51200', // 50MB max
            'account_id' => 'required|string',
            'integration_id' => 'nullable|integer',
        ]);

        try {
            // Store uploaded file temporarily
            $file = $request->file('file');
            $filename = 'facebook_import_' . time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('imports', $filename, 'local');
            $fullPath = storage_path('app/' . $path);

            Log::info('CSV upload received', [
                'filename' => $filename,
                'size' => $file->getSize(),
                'account_id' => $request->account_id,
            ]);

            // Get integration
            $integration = null;
            if ($request->integration_id) {
                $integration = Integration::find($request->integration_id);
            } else {
                // Get first active Facebook integration
                $integration = Integration::where('platform', 'facebook')
                    ->where('status', 'active')
                    ->first();
            }

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Facebook integration found'
                ], 400);
            }

            // Run import command
            Artisan::call('facebook:import-csv', [
                'file' => $fullPath,
                '--account-id' => $request->account_id,
                '--integration-id' => $integration->id,
            ]);

            $output = Artisan::output();

            // Parse output to get stats
            $stats = $this->parseImportOutput($output);

            // Clean up uploaded file
            Storage::disk('local')->delete($path);

            Log::info('CSV import completed', [
                'filename' => $filename,
                'stats' => $stats,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'stats' => $stats,
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            Log::error('CSV import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview CSV file contents before import
     */
    public function previewCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:51200',
        ]);

        try {
            $file = $request->file('file');
            $handle = fopen($file->getRealPath(), 'r');

            if (!$handle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not read CSV file'
                ], 400);
            }

            // Read header
            $headers = fgetcsv($handle);
            if (!$headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid CSV file - no headers found'
                ], 400);
            }

            // Normalize headers
            $headers = array_map(function($h) {
                return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
            }, $headers);

            // Read first 10 data rows for preview
            $preview = [];
            $rowCount = 0;
            while (($row = fgetcsv($handle)) !== false && $rowCount < 10) {
                $preview[] = array_combine($headers, $row);
                $rowCount++;
            }

            // Get total row count (approximate)
            fseek($handle, 0);
            $totalRows = 0;
            while (fgets($handle) !== false) {
                $totalRows++;
            }
            $totalRows = max(0, $totalRows - 1); // Subtract header row

            fclose($handle);

            // Detect format
            $format = $this->detectFormat($headers);

            return response()->json([
                'success' => true,
                'headers' => $headers,
                'preview' => $preview,
                'total_rows' => $totalRows,
                'detected_format' => $format,
                'recommendations' => $this->getFormatRecommendations($headers, $format),
            ]);

        } catch (\Exception $e) {
            Log::error('CSV preview failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload and import historical Google Ads CSV data
     */
    public function importGoogleAdsCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:51200', // 50MB max
            'customer_id' => 'required|string',
            'integration_id' => 'nullable|integer',
        ]);

        try {
            // Store uploaded file temporarily
            $file = $request->file('file');
            $filename = 'google_ads_import_' . time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('imports', $filename, 'local');
            $fullPath = storage_path('app/' . $path);

            Log::info('Google Ads CSV upload received', [
                'filename' => $filename,
                'size' => $file->getSize(),
                'customer_id' => $request->customer_id,
            ]);

            // Get integration
            $integration = null;
            if ($request->integration_id) {
                $integration = Integration::find($request->integration_id);
            } else {
                // Get first active Google integration
                $integration = Integration::where('platform', 'google')
                    ->where('status', 'active')
                    ->first();
            }

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Google Ads integration found'
                ], 400);
            }

            // Run import command
            Artisan::call('google-ads:import-csv', [
                'file' => $fullPath,
                '--customer-id' => $request->customer_id,
                '--integration-id' => $integration->id,
            ]);

            $output = Artisan::output();

            // Parse output to get stats
            $stats = $this->parseImportOutput($output);

            // Clean up uploaded file
            Storage::disk('local')->delete($path);

            Log::info('Google Ads CSV import completed', [
                'filename' => $filename,
                'stats' => $stats,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'stats' => $stats,
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            Log::error('Google Ads CSV import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import status/history
     */
    public function getImportHistory(Request $request)
    {
        try {
            // Read import logs
            $logPath = storage_path('logs/laravel.log');

            if (!file_exists($logPath)) {
                return response()->json([
                    'success' => true,
                    'imports' => [],
                ]);
            }

            // Parse last 100 import log entries
            $imports = [];
            $lines = array_slice(file($logPath), -1000);

            foreach ($lines as $line) {
                if (strpos($line, 'CSV import completed') !== false) {
                    $imports[] = [
                        'timestamp' => $this->extractTimestamp($line),
                        'message' => $this->extractMessage($line),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'imports' => array_slice($imports, -10), // Last 10 imports
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch import history'
            ], 500);
        }
    }

    /**
     * Parse Artisan command output to extract stats
     */
    private function parseImportOutput(string $output): array
    {
        $stats = [
            'rows_processed' => 0,
            'campaigns_created' => 0,
            'metrics_created' => 0,
            'metrics_updated' => 0,
            'errors' => 0,
        ];

        // Parse table output
        if (preg_match('/Rows Processed\s+\|\s+(\d+)/', $output, $matches)) {
            $stats['rows_processed'] = (int) str_replace(',', '', $matches[1]);
        }
        if (preg_match('/Campaigns Created\s+\|\s+(\d+)/', $output, $matches)) {
            $stats['campaigns_created'] = (int) str_replace(',', '', $matches[1]);
        }
        if (preg_match('/Metrics Created\s+\|\s+(\d+)/', $output, $matches)) {
            $stats['metrics_created'] = (int) str_replace(',', '', $matches[1]);
        }
        if (preg_match('/Metrics Updated\s+\|\s+(\d+)/', $output, $matches)) {
            $stats['metrics_updated'] = (int) str_replace(',', '', $matches[1]);
        }
        if (preg_match('/Errors\s+\|\s+(\d+)/', $output, $matches)) {
            $stats['errors'] = (int) str_replace(',', '', $matches[1]);
        }

        return $stats;
    }

    /**
     * Detect CSV format from headers
     */
    private function detectFormat(array $headers): string
    {
        if (in_array('campaign name', $headers) && in_array('reach', $headers)) {
            return 'Facebook Campaign Insights';
        }
        if (in_array('ad name', $headers) || in_array('ad id', $headers)) {
            return 'Facebook Ad Insights';
        }
        return 'Generic/Custom Format';
    }

    /**
     * Get recommendations based on detected format
     */
    private function getFormatRecommendations(array $headers, string $format): array
    {
        $recommendations = [];

        // Check for essential columns
        $essential = ['date', 'campaign name', 'spend', 'impressions'];
        $missing = array_filter($essential, function($col) use ($headers) {
            return !in_array(strtolower($col), $headers) &&
                   !in_array(str_replace(' ', '_', strtolower($col)), $headers) &&
                   !in_array('reporting starts', $headers); // Alternative for date
        });

        if (!empty($missing)) {
            $recommendations[] = 'Missing essential columns: ' . implode(', ', $missing);
        }

        // Check date column
        $hasDate = in_array('date', $headers) ||
                   in_array('day', $headers) ||
                   in_array('reporting starts', $headers) ||
                   in_array('reporting_starts', $headers) ||
                   in_array('date_start', $headers);

        if (!$hasDate) {
            $recommendations[] = 'No date column detected. Import may not work correctly.';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'CSV format looks good! Ready to import.';
        }

        return $recommendations;
    }

    private function extractTimestamp(string $line): string
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function extractMessage(string $line): string
    {
        if (preg_match('/production\.INFO: (.+)$/', $line, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
