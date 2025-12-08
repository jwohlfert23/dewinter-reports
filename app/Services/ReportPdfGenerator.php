<?php

declare(strict_types=1);

namespace App\Services;

use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
        $response = Http::withOptions(['allow_redirects' => true])->get($url);

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
     * @return array<string, array<int, array<string, string>>>
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
                $ordered[$status] = $grouped[$status];
                unset($grouped[$status]);
            }
        }

        foreach ($grouped as $status => $candidates) {
            $ordered[$status] = $candidates;
        }

        return $ordered;
    }

    /**
     * Generate PDF from HTML using Snappy (wkhtmltopdf wrapper).
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

        SnappyPdf::loadHTML($html)
            ->setOption('page-size', 'Letter')
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('disable-smart-shrinking', true)
            ->save($outputPath);

        return $outputPath;
    }
}
