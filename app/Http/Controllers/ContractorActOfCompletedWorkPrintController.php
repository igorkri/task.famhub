<?php

namespace App\Http\Controllers;

use App\Models\ContractorActOfCompletedWork;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ContractorActOfCompletedWorkPrintController extends Controller
{
    /**
     * Експорт вибраних актів в один PDF (кожен акт — окрема сторінка).
     */
    public function exportBulkPdf(Request $request): Response
    {
        $ids = $request->query('ids');
        if (is_string($ids)) {
            $ids = array_filter(array_map('intval', explode(',', $ids)));
        }
        if (empty($ids)) {
            abort(422, 'Не вказано жодного акту для експорту.');
        }

        $acts = ContractorActOfCompletedWork::query()
            ->whereIn('id', $ids)
            ->orderBy('date')
            ->orderBy('number')
            ->with(['contractor', 'customer', 'items' => fn ($q) => $q->orderBy('sequence_number')])
            ->get();

        if ($acts->isEmpty()) {
            abort(404, 'Акти не знайдено.');
        }

        $filename = 'akty-' . now()->format('Y-m-d-His') . '.pdf';

        return Pdf::loadView(
            'contractor-act-of-completed-works.print-multi',
            ['acts' => $acts, 'isPdf' => true],
            [],
            'UTF-8',
        )
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
    public function __invoke(Request $request, ContractorActOfCompletedWork $act): View|Response
    {
        $act->load(['contractor', 'customer', 'items' => fn ($q) => $q->orderBy('sequence_number')]);

        if ($request->query('format') === 'pdf') {
            return $this->downloadPdf($act);
        }

        return view('contractor-act-of-completed-works.print', [
            'act' => $act,
            'isPdf' => false,
        ]);
    }

    public function pdf(ContractorActOfCompletedWork $act): Response
    {
        $act->load(['contractor', 'customer', 'items' => fn ($q) => $q->orderBy('sequence_number')]);

        return $this->downloadPdf($act);
    }

    private function downloadPdf(ContractorActOfCompletedWork $act): Response
    {
        $filename = 'akt-' . $act->number . '-' . $act->date?->format('Y-m-d') . '.pdf';

        return Pdf::loadView(
            'contractor-act-of-completed-works.print',
            [
                'act' => $act,
                'isPdf' => true,
            ],
            [],
            'UTF-8',
        )
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
