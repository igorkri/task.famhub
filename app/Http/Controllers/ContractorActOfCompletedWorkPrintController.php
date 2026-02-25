<?php

namespace App\Http\Controllers;

use App\Models\ContractorActOfCompletedWork;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ContractorActOfCompletedWorkPrintController extends Controller
{
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
