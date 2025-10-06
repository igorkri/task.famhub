<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Services\AsanaService;

class AsanaTestController extends Controller
{
    public function projects(AsanaService $asanaService): Response
    {
        $projects = $asanaService->getProjects();
        return response($projects);
    }
}

