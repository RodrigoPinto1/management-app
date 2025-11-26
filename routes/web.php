<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\EntityController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('clients', function () {
        return Inertia::render('entities/Index', ['type' => 'client']);
    })->name('clients');

    Route::get('suppliers', function () {
        return Inertia::render('entities/Index', ['type' => 'supplier']);
    })->name('suppliers');

    // JSON endpoints used by the frontend
    Route::get('entities/list', [EntityController::class, 'list']);
    Route::get('entities/check-nif', [EntityController::class, 'checkNif']);
    Route::post('entities', [EntityController::class, 'store']);

    // Settings - Articles
    Route::get('settings/articles', [\App\Http\Controllers\ArticleController::class, 'index']);
    Route::get('settings/articles/list', [\App\Http\Controllers\ArticleController::class, 'list']);
    Route::post('settings/articles', [\App\Http\Controllers\ArticleController::class, 'store']);

    // Proposals
    Route::get('proposals', [\App\Http\Controllers\ProposalController::class, 'index']);
    Route::get('proposals/list', [\App\Http\Controllers\ProposalController::class, 'list']);
    Route::get('proposals/{proposal}', [\App\Http\Controllers\ProposalController::class, 'show']);
    Route::post('proposals', [\App\Http\Controllers\ProposalController::class, 'store']);
    Route::post('proposals/{proposal}/convert', [\App\Http\Controllers\ProposalController::class, 'convert']);
});
