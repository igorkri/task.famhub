<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/asana-test-projects', [\App\Http\Controllers\AsanaTestController::class, 'projects']);
