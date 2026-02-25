<?php

namespace App\Http\Controllers;

use App\Models\ContractorActOfCompletedWork;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractorActOfCompletedWorkPrintController extends Controller
{
    public function __invoke(Request $request, ContractorActOfCompletedWork $act): View
    {
        $act->load(['contractor', 'customer', 'items' => fn ($q) => $q->orderBy('sequence_number')]);

        return view('contractor-act-of-completed-works.print', [
            'act' => $act,
        ]);
    }
}
