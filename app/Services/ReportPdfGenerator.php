<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class ReportPdfGenerator
{
    /**
     * @param array{
     *     client_name: string,
     *     client_logo: string,
     *     date: string,
     *     position_title: string,
     *     google_sheet_url: string
     * } $data
     */
    public function generate(array $data): string
    {
        $candidates = $this->fetchCsvData($data['google_sheet_url']);
        $groupedCandidates = $this->groupByStatus($candidates);

        $html = view('pdf.report', [
            'clientName' => $data['client_name'],
            'clientLogo' => $data['client_logo'],
            'date' => $data['date'],
            'positionTitle' => $data['position_title'],
            'groupedCandidates' => $groupedCandidates,
        ])->render();

        return $this->generatePdfFromHtml($html, $data['client_name'], $data['position_title'], $data['date']);
    }

    /**
     * Fetch and parse CSV data from Google Sheets URL.
     *
     * @return array<int, array<string, string>>
     */
    protected function fetchCsvData(string $url): array
    {
        // Add cache-busting parameter to avoid Google Sheets caching
        $cacheBuster = (str_contains($url, '?') ? '&' : '?').'_t='.time();
        $response = Http::withOptions(['allow_redirects' => true])->get($url.$cacheBuster);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch CSV data from Google Sheets');
        }

        $csvContent = $response->body();
        $lines = array_filter(explode("\n", $csvContent));

        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $candidates = [];

        foreach ($lines as $line) {
            $values = str_getcsv($line);
            if (count($values) >= count($headers)) {
                $candidate = [];
                foreach ($headers as $index => $header) {
                    $candidate[trim($header)] = $values[$index] ?? '';
                }
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    /**
     * Group candidates by their status.
     *
     * @param  array<int, array<string, string>>  $candidates
     * @return array<string, array{candidates: array<int, array<string, string>>, hasInterviewDate: bool}>
     */
    protected function groupByStatus(array $candidates): array
    {
        $grouped = [];

        $statusOrder = [
            'Interviewing',
            'Submitted',
            'DeWinter Screening',
            'Screening',
            'Rejected',
            'DWP Rejected',
            'Pass - Compensation',
            'Pass - Location',
            'Not Interested',
        ];

        foreach ($candidates as $candidate) {
            $status = trim($candidate['Status'] ?? 'Unknown');
            if (! isset($grouped[$status])) {
                $grouped[$status] = [];
            }
            $grouped[$status][] = $candidate;
        }

        $ordered = [];
        foreach ($statusOrder as $status) {
            if (isset($grouped[$status])) {
                $ordered[$status] = $this->buildStatusGroup($status, $grouped[$status]);
                unset($grouped[$status]);
            }
        }

        foreach ($grouped as $status => $statusCandidates) {
            $ordered[$status] = $this->buildStatusGroup($status, $statusCandidates);
        }

        return $ordered;
    }

    /**
     * Build a status group with metadata about whether interview dates exist.
     *
     * @param  array<int, array<string, string>>  $candidates
     * @return array{candidates: array<int, array<string, string>>, hasInterviewDate: bool}
     */
    protected function buildStatusGroup(string $status, array $candidates): array
    {
        $hasInterviewDate = false;

        if ($status === 'Interviewing') {
            foreach ($candidates as $candidate) {
                $dateValue = trim($candidate['Date Interviewing'] ?? '');
                if ($dateValue !== '') {
                    $hasInterviewDate = true;
                    break;
                }
            }
        }

        return [
            'candidates' => $candidates,
            'hasInterviewDate' => $hasInterviewDate,
        ];
    }

    /**
     * Format interview date to m/d/Y format.
     */
    public static function formatInterviewDate(string $date): string
    {
        if (trim($date) === '') {
            return '';
        }

        try {
            return Carbon::parse($date)->format('n/j/Y');
        } catch (\Exception) {
            return $date;
        }
    }

    /**
     * Generate PDF from HTML using Browsershot (Puppeteer/Chromium wrapper).
     */
    protected function generatePdfFromHtml(string $html, string $clientName, string $positionTitle, string $date): string
    {
        $uuid = Str::uuid()->toString();
        $outputFilename = sprintf(
            '%s-%s %s %s Report.pdf',
            $uuid,
            $clientName,
            $positionTitle,
            date('n.j', strtotime($date))
        );
        $outputPath = storage_path('app/reports/'.$outputFilename);

        if (! is_dir(storage_path('app/reports'))) {
            mkdir(storage_path('app/reports'), 0755, true);
        }

        Browsershot::html($html)
            ->noSandbox()
//            ->setNodeBinary('/Users/jwohlfert/.nvm/versions/node/v22.19.0/bin/node')
//            ->setNpmBinary('/Users/jwohlfert/.nvm/versions/node/v22.19.0/bin/npm')
            ->format('Letter')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->save($outputPath);

        return $outputPath;
    }
}
