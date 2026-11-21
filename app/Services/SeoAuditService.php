<?php

namespace App\Services;

use App\Models\SeoTechnicalIssue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SeoAuditService
{
    /**
     * Lighthouse audit config: id => [severity, threshold, description_en, description_ar, recommended_fix]
     * Threshold logic: 'zero' = score === 0 is failing, 'low' = score < 0.5, 'high' = score < 0.9
     */
    protected const LIGHTHOUSE_AUDITS = [
        'is-crawlable' => [
            'severity' => 'critical',
            'threshold' => 'zero',
            'description_en' => 'Page is blocked from indexing',
            'description_ar' => 'الصفحة محظورة من الفهرسة',
            'fix' => 'Remove noindex directives from meta tags or HTTP headers.',
        ],
        'http-status-code' => [
            'severity' => 'critical',
            'threshold' => 'zero',
            'description_en' => 'Page returns error HTTP status code',
            'description_ar' => 'الصفحة تُرجع رمز حالة HTTP خاطئ',
            'fix' => 'Ensure the page returns a 2xx status code.',
        ],
        'robots-txt' => [
            'severity' => 'critical',
            'threshold' => 'zero',
            'description_en' => 'Invalid or missing robots.txt',
            'description_ar' => 'ملف robots.txt غير صالح أو مفقود',
            'fix' => 'Create a valid robots.txt file at the site root.',
        ],
        'meta-description' => [
            'severity' => 'high',
            'threshold' => 'low',
            'description_en' => 'Missing or inadequate meta description',
            'description_ar' => 'وصف الميتا مفقود أو غير كافٍ',
            'fix' => 'Add a unique, descriptive meta description (120-160 characters).',
        ],
        'document-title' => [
            'severity' => 'high',
            'threshold' => 'low',
            'description_en' => 'Missing or inadequate page title',
            'description_ar' => 'عنوان الصفحة مفقود أو غير كافٍ',
            'fix' => 'Add a unique, descriptive <title> tag (50-60 characters).',
        ],
        'canonical' => [
            'severity' => 'high',
            'threshold' => 'low',
            'description_en' => 'Missing canonical URL',
            'description_ar' => 'رابط canonical مفقود',
            'fix' => 'Add a <link rel="canonical"> tag pointing to the preferred URL.',
        ],
        'hreflang' => [
            'severity' => 'high',
            'threshold' => 'low',
            'description_en' => 'Invalid hreflang tags',
            'description_ar' => 'علامات hreflang غير صالحة',
            'fix' => 'Fix hreflang annotations to use valid language/region codes and reciprocal links.',
        ],
        'crawlable-anchors' => [
            'severity' => 'high',
            'threshold' => 'low',
            'description_en' => 'Links are not crawlable',
            'description_ar' => 'الروابط غير قابلة للزحف',
            'fix' => 'Use proper <a href="..."> tags instead of JavaScript-based navigation.',
        ],
        'structured-data' => [
            'severity' => 'high',
            'threshold' => 'low',
            'description_en' => 'Structured data has errors',
            'description_ar' => 'البيانات المنظمة تحتوي على أخطاء',
            'fix' => 'Validate and fix structured data using Google Rich Results Test.',
        ],
        'image-alt' => [
            'severity' => 'medium',
            'threshold' => 'high',
            'description_en' => 'Images missing alt text',
            'description_ar' => 'الصور تفتقر إلى نص بديل',
            'fix' => 'Add descriptive alt attributes to all meaningful images.',
        ],
        'link-text' => [
            'severity' => 'medium',
            'threshold' => 'high',
            'description_en' => 'Non-descriptive link text',
            'description_ar' => 'نص الرابط غير وصفي',
            'fix' => 'Replace generic link text ("click here") with descriptive anchor text.',
        ],
        'heading-order' => [
            'severity' => 'medium',
            'threshold' => 'high',
            'description_en' => 'Heading hierarchy is skipped',
            'description_ar' => 'تسلسل العناوين غير مرتب',
            'fix' => 'Use headings in sequential order (h1 → h2 → h3) without skipping levels.',
        ],
        'tap-targets' => [
            'severity' => 'medium',
            'threshold' => 'high',
            'description_en' => 'Tap targets are too small',
            'description_ar' => 'أهداف اللمس صغيرة جدًا',
            'fix' => 'Make tap targets at least 48x48 CSS pixels with adequate spacing.',
        ],
        'font-size' => [
            'severity' => 'medium',
            'threshold' => 'high',
            'description_en' => 'Text too small on mobile',
            'description_ar' => 'النص صغير جدًا على الهاتف',
            'fix' => 'Use a base font size of at least 16px and proper viewport settings.',
        ],
        'render-blocking-resources' => [
            'severity' => 'low',
            'threshold' => 'low',
            'description_en' => 'Render-blocking JavaScript or CSS',
            'description_ar' => 'ملفات JavaScript أو CSS تمنع العرض',
            'fix' => 'Defer non-critical JS/CSS or inline critical styles.',
        ],
        'total-byte-weight' => [
            'severity' => 'low',
            'threshold' => 'low',
            'description_en' => 'Excessive page weight',
            'description_ar' => 'حجم الصفحة مفرط',
            'fix' => 'Compress images, minify JS/CSS, and remove unused code.',
        ],
    ];

    /**
     * Process Lighthouse audits from PageSpeed results and upsert issues.
     */
    public function processPageSpeedAudits(int $tenantId, string $pageUrl, array $mobileResult, array $desktopResult): void
    {
        try {
            $mobileAudits = $mobileResult['audits'] ?? [];
            $desktopAudits = $desktopResult['audits'] ?? [];

            // Merge: take the worse (lower) score per audit
            $merged = [];
            foreach (array_keys(self::LIGHTHOUSE_AUDITS) as $auditId) {
                $mScore = $mobileAudits[$auditId]['score'] ?? null;
                $dScore = $desktopAudits[$auditId]['score'] ?? null;

                if ($mScore === null && $dScore === null) {
                    continue; // audit not present in either result
                }

                $merged[$auditId] = min(
                    $mScore ?? 1.0,
                    $dScore ?? 1.0
                );
            }

            $failingTypes = [];

            foreach ($merged as $auditId => $score) {
                $config = self::LIGHTHOUSE_AUDITS[$auditId];
                $failing = match ($config['threshold']) {
                    'zero' => $score == 0,
                    'low' => $score < 0.5,
                    'high' => $score < 0.9,
                    default => false,
                };

                if ($failing) {
                    $issueType = 'lighthouse:' . $auditId;
                    $failingTypes[] = $issueType;
                    $this->upsertIssue(
                        tenantId: $tenantId,
                        issueType: $issueType,
                        pageUrl: $pageUrl,
                        severity: $config['severity'],
                        descriptionEn: $config['description_en'],
                        descriptionAr: $config['description_ar'],
                        recommendedFix: $config['fix'],
                    );
                }
            }

            $this->autoResolveStale($tenantId, $pageUrl, 'lighthouse:', $failingTypes);

            $this->processScoreIssues($tenantId, $pageUrl, $mobileResult, $desktopResult);
            $this->processCwvIssues($tenantId, $pageUrl, $mobileResult, $desktopResult);
        } catch (\Exception $e) {
            Log::warning('SeoAuditService: Failed processing PageSpeed audits', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process Search Console coverage data and upsert issues.
     */
    public function processSearchConsoleIssues(int $tenantId, string $siteUrl, array $coverageData): void
    {
        try {
            $failingTypes = [];

            // Overall index rate check
            $totalSubmitted = 0;
            $totalIndexed = 0;
            foreach ($coverageData as $sitemap) {
                $totalSubmitted += $sitemap['submitted'] ?? 0;
                $totalIndexed += $sitemap['indexed'] ?? 0;
            }
            $overallIndexRate = $totalSubmitted > 0
                ? round(($totalIndexed / $totalSubmitted) * 100, 1)
                : 100;

            if ($overallIndexRate < 50 && $totalSubmitted > 0) {
                $issueType = 'sc:low-index-rate';
                $failingTypes[] = $issueType;
                $this->upsertIssue(
                    tenantId: $tenantId,
                    issueType: $issueType,
                    pageUrl: $siteUrl,
                    severity: 'critical',
                    descriptionEn: "Overall index rate is {$overallIndexRate}% (below 50%)",
                    descriptionAr: "معدل الفهرسة الإجمالي {$overallIndexRate}% (أقل من 50%)",
                    recommendedFix: 'Review Search Console coverage report. Fix crawl errors and ensure important pages are not blocked.',
                );
            }

            // Per-sitemap checks
            foreach ($coverageData as $sitemap) {
                $path = $sitemap['path'] ?? $sitemap['sitemap'] ?? 'unknown';
                $errors = $sitemap['errors'] ?? 0;
                $warnings = $sitemap['warnings'] ?? 0;
                $submitted = $sitemap['submitted'] ?? 0;
                $indexed = $sitemap['indexed'] ?? 0;
                $sitemapIndexRate = $submitted > 0 ? round(($indexed / $submitted) * 100, 1) : 100;

                if ($errors > 0) {
                    $issueType = 'sc:sitemap-errors:' . $path;
                    $failingTypes[] = $issueType;
                    $this->upsertIssue(
                        tenantId: $tenantId,
                        issueType: $issueType,
                        pageUrl: $siteUrl,
                        severity: 'high',
                        descriptionEn: "Sitemap \"{$path}\" has {$errors} error(s)",
                        descriptionAr: "خريطة الموقع \"{$path}\" بها {$errors} خطأ/أخطاء",
                        recommendedFix: 'Review sitemap errors in Search Console and fix invalid URLs.',
                    );
                }

                if ($warnings > 0) {
                    $issueType = 'sc:sitemap-warnings:' . $path;
                    $failingTypes[] = $issueType;
                    $this->upsertIssue(
                        tenantId: $tenantId,
                        issueType: $issueType,
                        pageUrl: $siteUrl,
                        severity: 'medium',
                        descriptionEn: "Sitemap \"{$path}\" has {$warnings} warning(s)",
                        descriptionAr: "خريطة الموقع \"{$path}\" بها {$warnings} تحذير/تحذيرات",
                        recommendedFix: 'Review sitemap warnings in Search Console.',
                    );
                }

                if ($sitemapIndexRate < 30 && $submitted > 0) {
                    $issueType = 'sc:sitemap-low-index:' . $path;
                    $failingTypes[] = $issueType;
                    $this->upsertIssue(
                        tenantId: $tenantId,
                        issueType: $issueType,
                        pageUrl: $siteUrl,
                        severity: 'medium',
                        descriptionEn: "Sitemap \"{$path}\" index rate is {$sitemapIndexRate}% ({$indexed}/{$submitted})",
                        descriptionAr: "خريطة الموقع \"{$path}\" معدل الفهرسة {$sitemapIndexRate}% ({$indexed}/{$submitted})",
                        recommendedFix: 'Remove non-indexable URLs from the sitemap or fix crawl/index issues.',
                    );
                }
            }

            $this->autoResolveStale($tenantId, $siteUrl, 'sc:', $failingTypes);
        } catch (\Exception $e) {
            Log::warning('SeoAuditService: Failed processing Search Console issues', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Detect category-score issues (performance, accessibility, best-practices).
     * SEO category is skipped — already covered by individual Lighthouse audits.
     */
    protected function processScoreIssues(int $tenantId, string $pageUrl, array $mobileResult, array $desktopResult): void
    {
        $categories = [
            'performance' => ['label_en' => 'Performance', 'label_ar' => 'الأداء'],
            'accessibility' => ['label_en' => 'Accessibility', 'label_ar' => 'إمكانية الوصول'],
            'best_practices' => ['label_en' => 'Best Practices', 'label_ar' => 'أفضل الممارسات'],
        ];

        $failingTypes = [];

        foreach ($categories as $key => $labels) {
            $mobileScore = $mobileResult['scores'][$key] ?? null;
            $desktopScore = $desktopResult['scores'][$key] ?? null;

            if ($mobileScore === null && $desktopScore === null) {
                continue;
            }

            // Take the worst (lowest) score
            $score = min($mobileScore ?? 100, $desktopScore ?? 100);

            if ($score >= 90) {
                continue;
            }

            $issueType = 'score:' . $key;
            $failingTypes[] = $issueType;

            if ($score < 50) {
                $severity = 'critical';
            } elseif ($key === 'performance') {
                $severity = 'high';
            } else {
                $severity = 'medium';
            }

            $this->upsertIssue(
                tenantId: $tenantId,
                issueType: $issueType,
                pageUrl: $pageUrl,
                severity: $severity,
                descriptionEn: "{$labels['label_en']} score is {$score}/100",
                descriptionAr: "نتيجة {$labels['label_ar']} هي {$score}/100",
                recommendedFix: "Improve {$labels['label_en']} score to 90+ by addressing Lighthouse recommendations.",
            );
        }

        $this->autoResolveStale($tenantId, $pageUrl, 'score:', $failingTypes);
    }

    /**
     * Detect Core Web Vitals issues from PageSpeed results.
     */
    protected function processCwvIssues(int $tenantId, string $pageUrl, array $mobileResult, array $desktopResult): void
    {
        $metrics = [
            'lcp' => [
                'label' => 'LCP (Largest Contentful Paint)',
                'label_ar' => 'أكبر عنصر محتوى مرئي (LCP)',
                'severity_poor' => 'critical',
                'severity_needs_improvement' => 'high',
                'fix' => 'Optimize images, reduce server response time, and eliminate render-blocking resources.',
            ],
            'cls' => [
                'label' => 'CLS (Cumulative Layout Shift)',
                'label_ar' => 'إزاحة التخطيط التراكمية (CLS)',
                'severity_poor' => 'critical',
                'severity_needs_improvement' => 'high',
                'fix' => 'Set explicit dimensions on images/videos, avoid inserting content above existing content.',
            ],
            'fcp' => [
                'label' => 'FCP (First Contentful Paint)',
                'label_ar' => 'أول رسم محتوى (FCP)',
                'severity_poor' => 'high',
                'severity_needs_improvement' => 'medium',
                'fix' => 'Reduce server response time, eliminate render-blocking resources, and optimize CSS delivery.',
            ],
            'tbt' => [
                'label' => 'TBT (Total Blocking Time)',
                'label_ar' => 'إجمالي وقت الحظر (TBT)',
                'severity_poor' => 'high',
                'severity_needs_improvement' => 'medium',
                'fix' => 'Reduce JavaScript execution time, break up long tasks, and defer non-critical scripts.',
            ],
            'tti' => [
                'label' => 'TTI (Time to Interactive)',
                'label_ar' => 'وقت التفاعل (TTI)',
                'severity_poor' => 'high',
                'severity_needs_improvement' => 'medium',
                'fix' => 'Minimize main-thread work, reduce JavaScript payload, and defer unused JS.',
            ],
            'si' => [
                'label' => 'SI (Speed Index)',
                'label_ar' => 'مؤشر السرعة (SI)',
                'severity_poor' => 'medium',
                'severity_needs_improvement' => 'low',
                'fix' => 'Optimize critical rendering path and ensure above-the-fold content loads quickly.',
            ],
        ];

        $failingTypes = [];

        foreach ($metrics as $key => $config) {
            $mobileCwv = $mobileResult['core_web_vitals'][$key] ?? null;
            $desktopCwv = $desktopResult['core_web_vitals'][$key] ?? null;

            // Pick the worse rating between mobile and desktop
            $worstRating = null;
            $displayValue = null;

            foreach ([$mobileCwv, $desktopCwv] as $cwv) {
                if (!$cwv || !isset($cwv['score'])) {
                    continue;
                }
                $rating = $cwv['score']; // 'good', 'needs_improvement', or 'poor'
                if ($rating === 'poor') {
                    $worstRating = 'poor';
                    $displayValue = $cwv['display_value'] ?? $cwv['display'] ?? $displayValue;
                } elseif ($rating === 'needs_improvement' && $worstRating !== 'poor') {
                    $worstRating = 'needs_improvement';
                    $displayValue = $cwv['display_value'] ?? $cwv['display'] ?? $displayValue;
                }
            }

            if ($worstRating === null) {
                continue;
            }

            $issueType = 'cwv:' . $key;
            $failingTypes[] = $issueType;
            $severity = $config['severity_' . $worstRating];
            $metricLabel = strtoupper($key);
            $valueStr = $displayValue ? " is {$displayValue}" : '';

            $this->upsertIssue(
                tenantId: $tenantId,
                issueType: $issueType,
                pageUrl: $pageUrl,
                severity: $severity,
                descriptionEn: "{$metricLabel}{$valueStr} ({$worstRating})",
                descriptionAr: "{$config['label_ar']}{$valueStr} ({$worstRating})",
                recommendedFix: $config['fix'],
            );
        }

        $this->autoResolveStale($tenantId, $pageUrl, 'cwv:', $failingTypes);
    }

    /**
     * Smart upsert: dedup on (tenant_id, issue_type, page_url) with status-aware logic.
     */
    protected function upsertIssue(
        int $tenantId,
        string $issueType,
        string $pageUrl,
        string $severity,
        string $descriptionEn,
        string $descriptionAr,
        string $recommendedFix,
    ): void {
        $existing = SeoTechnicalIssue::where('tenant_id', $tenantId)
            ->where('issue_type', $issueType)
            ->where('page_url', $pageUrl)
            ->first();

        if (!$existing) {
            SeoTechnicalIssue::create([
                'tenant_id' => $tenantId,
                'issue_type' => $issueType,
                'page_url' => $pageUrl,
                'severity' => $severity,
                'description_en' => $descriptionEn,
                'description_ar' => $descriptionAr,
                'recommended_fix' => $recommendedFix,
                'current_status' => 'open',
                'date_found' => now(),
            ]);
            return;
        }

        // User dismissed — respect their decision
        if ($existing->current_status === 'wont_fix') {
            return;
        }

        // Was resolved but regressed — re-open
        if ($existing->current_status === 'resolved') {
            $existing->update([
                'current_status' => 'open',
                'severity' => $severity,
                'description_en' => $descriptionEn,
                'description_ar' => $descriptionAr,
                'recommended_fix' => $recommendedFix,
                'date_fixed' => null,
                'date_found' => now(),
            ]);
            return;
        }

        // open or in_progress — update details, keep status
        $existing->update([
            'severity' => $severity,
            'description_en' => $descriptionEn,
            'description_ar' => $descriptionAr,
            'recommended_fix' => $recommendedFix,
        ]);
    }

    /**
     * Auto-resolve issues that are no longer failing.
     */
    protected function autoResolveStale(int $tenantId, string $pageUrl, string $prefix, array $failingTypes): void
    {
        SeoTechnicalIssue::where('tenant_id', $tenantId)
            ->where('page_url', $pageUrl)
            ->where('issue_type', 'like', $prefix . '%')
            ->whereIn('current_status', ['open', 'in_progress'])
            ->whereNotIn('issue_type', $failingTypes)
            ->update([
                'current_status' => 'resolved',
                'date_fixed' => now(),
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), '\n[Auto-resolved: issue no longer detected]')"),
            ]);
    }
}
