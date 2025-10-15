<?php

namespace App\Http\Controllers;

use App\Services\AsanaService;
use Illuminate\Http\Response;

class AsanaTestController extends Controller
{
    public function projects(AsanaService $asanaService): Response
    {
        $projects = $asanaService->getProjects();

        return response($projects);
    }
}
