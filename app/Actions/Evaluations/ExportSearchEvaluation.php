<?php

namespace App\Actions\Evaluations;

use App\Models\SearchEvaluation;
use App\Services\Evaluations\JudgementsService;
use Symfony\Component\HttpFoundation\StreamedResponse;

readonly class ExportSearchEvaluation
{
    public function __construct(private JudgementsService $judgementsService)
    {
    }

    public function export(SearchEvaluation $evaluation): StreamedResponse
    {
        if (!$evaluation->isFinished()) {
            throw new \RuntimeException('Failed to export evaluation: evaluation is not finished');
        }

        $evaluation->load('keywords.snapshots.feedbacks');

        $callback = function () use ($evaluation) {
            $handle = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($handle, ['grade', 'keyword', 'doc']);

            $this->judgementsService
                ->process($evaluation, function ($grade, $keyword, $doc, $position) use ($handle) {
                    fputcsv($handle, [$grade, $keyword, $doc]);
                });

            fclose($handle);
        };

        return response()->streamDownload($callback, 'export.csv', [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=export.csv',
        ]);
    }
}
