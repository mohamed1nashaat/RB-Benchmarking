<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LogoFetchService
{
    /**
     * Fetch and store logo for a tenant
     */
    public function fetchLogo(Tenant $tenant, bool $force = false): array
    {
        // Skip if logo already exists and not forcing
        if (!$force && $tenant->logo_path) {
            return [
                'success' => false,
                'message' => 'Logo already exists',
                'skipped' => true,
            ];
        }

        try {
            // Try multiple sources
            $logoUrl = $this->findLogoUrl($tenant);

            if (!$logoUrl) {
                return [
                    'success' => false,
                    'message' => 'No logo found',
                ];
            }

            // Download and store the logo
            $path = $this->downloadAndStore($logoUrl, $tenant);

            if ($path) {
                // Update tenant record
                $tenant->update([
                    'logo_path' => $path,
                    'logo_url' => $logoUrl,
                ]);

                return [
                    'success' => true,
                    'message' => 'Logo fetched successfully',
                    'path' => $path,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to download logo',
            ];

        } catch (\Exception $e) {
            Log::error("Logo fetch failed for tenant {$tenant->id}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Find logo URL using multiple sources
     */
    private function findLogoUrl(Tenant $tenant): ?string
    {
        // Try Clearbit Logo API first (free, no auth required)
        $domain = $this->guessDomain($tenant->name);
        if ($domain) {
            $clearbitUrl = "https://logo.clearbit.com/{$domain}";
            if ($this->urlExists($clearbitUrl)) {
                return $clearbitUrl;
            }
        }

        // Try Logo.dev API
        if ($domain) {
            $logoDevUrl = "https://img.logo.dev/{$domain}?token=pk_X-7zAl5QR-CYLGbLB_0CRA";
            if ($this->urlExists($logoDevUrl)) {
                return $logoDevUrl;
            }
        }

        // Fallback: Try to search Google Images (simple scraping)
        return $this->searchGoogleImages($tenant->name);
    }

    /**
     * Guess domain from company name
     */
    private function guessDomain(string $companyName): ?string
    {
        // Clean up the company name
        $cleaned = strtolower($companyName);

        // Remove common prefixes/suffixes
        $cleaned = preg_replace('/^(rbp-\d+\s*-?\s*)/i', '', $cleaned);
        $cleaned = preg_replace('/\s*(ltd|llc|inc|corp|co|company|group|international)\s*$/i', '', $cleaned);

        // Remove special characters
        $cleaned = preg_replace('/[^a-z0-9\s-]/', '', $cleaned);
        $cleaned = trim($cleaned);

        // Convert spaces and hyphens to nothing for domain guess
        $domain = str_replace([' ', '-'], '', $cleaned);

        if (strlen($domain) < 3) {
            return null;
        }

        return $domain . '.com';
    }

    /**
     * Check if URL exists and returns an image
     */
    private function urlExists(string $url): bool
    {
        try {
            $response = Http::timeout(5)->head($url);

            if (!$response->successful()) {
                return false;
            }

            // Check if it's an image
            $contentType = $response->header('Content-Type');
            return $contentType && str_starts_with($contentType, 'image/');

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Search Google Images for company logo
     */
    private function searchGoogleImages(string $companyName): ?string
    {
        try {
            $searchQuery = urlencode($companyName . ' logo');
            $url = "https://www.google.com/search?q={$searchQuery}&tbm=isch";

            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();

            // Try to extract first image URL from Google Images
            // Look for data-src or src attributes in img tags
            if (preg_match('/"(https?:\/\/[^"]+\.(jpg|jpeg|png|webp|svg))[",?]/', $html, $matches)) {
                return $matches[1];
            }

            // Alternative pattern for Google Images
            if (preg_match('/\["(https?:\/\/[^"]+)",\d+,\d+\]/', $html, $matches)) {
                $potentialUrl = $matches[1];
                // Decode escaped characters
                $potentialUrl = json_decode('"' . $potentialUrl . '"');
                if ($this->urlExists($potentialUrl)) {
                    return $potentialUrl;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::warning("Google Images search failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Download and store logo image
     */
    private function downloadAndStore(string $url, Tenant $tenant): ?string
    {
        try {
            // Download the image
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $imageContent = $response->body();

            // Determine file extension
            $extension = $this->getExtensionFromUrl($url);
            if (!$extension) {
                $extension = $this->getExtensionFromContent($imageContent);
            }

            // Generate filename
            $filename = $tenant->id . '-' . Str::slug($tenant->name) . '.' . $extension;
            $path = 'logos/' . $filename;

            // Store in public disk
            Storage::disk('public')->put($path, $imageContent);

            return $path;

        } catch (\Exception $e) {
            Log::error("Logo download failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get file extension from URL
     */
    private function getExtensionFromUrl(string $url): ?string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';

        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $path, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }

    /**
     * Get file extension from image content
     */
    private function getExtensionFromContent(string $content): string
    {
        // Check magic bytes
        $magicBytes = substr($content, 0, 4);

        if (str_starts_with($magicBytes, "\xFF\xD8\xFF")) {
            return 'jpg';
        } elseif (str_starts_with($magicBytes, "\x89PNG")) {
            return 'png';
        } elseif (str_starts_with($magicBytes, "GIF8")) {
            return 'gif';
        } elseif (str_starts_with($magicBytes, "RIFF") && strpos($content, 'WEBP') !== false) {
            return 'webp';
        } elseif (str_starts_with($content, '<?xml') || str_starts_with($content, '<svg')) {
            return 'svg';
        }

        return 'png'; // Default fallback
    }
}
