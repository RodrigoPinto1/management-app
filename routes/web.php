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
    // Users Access Control
    Route::get('users', [\App\Http\Controllers\UserAccessController::class, 'index'])->name('users.index');
    Route::get('users/list', [\App\Http\Controllers\UserAccessController::class, 'list']);
    Route::post('users', [\App\Http\Controllers\UserAccessController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [\App\Http\Controllers\UserAccessController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [\App\Http\Controllers\UserAccessController::class, 'destroy'])->name('users.destroy');

    Route::get('clients', function () {
        return Inertia::render('entities/Index', ['type' => 'client']);
    })->name('clients');

    Route::get('suppliers', function () {
        return Inertia::render('entities/Index', ['type' => 'supplier']);
    })->name('suppliers');

    // JSON endpoints used by the frontend
    Route::get('entities/list', [EntityController::class, 'list']);
    // Users list for calendar filter
    Route::get('calendar/users', function () {
        return response()->json(['data' => \App\Models\User::orderBy('name')->get()]);
    });
    Route::get('entities/check-nif', [EntityController::class, 'checkNif']);
    Route::get('entities/vies', [EntityController::class, 'vies']);
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

    // Calendar
    Route::get('calendar', [\App\Http\Controllers\CalendarController::class, 'index'])->name('calendar');
    Route::get('calendar/events', [\App\Http\Controllers\CalendarController::class, 'events']);
    Route::post('calendar/events', [\App\Http\Controllers\CalendarController::class, 'store']);
    Route::put('calendar/events/{calendarEvent}', [\App\Http\Controllers\CalendarController::class, 'update']);
    Route::delete('calendar/events/{calendarEvent}', [\App\Http\Controllers\CalendarController::class, 'destroy']);
    Route::get('calendar/types', [\App\Http\Controllers\CalendarController::class, 'types']);
    Route::get('calendar/actions', [\App\Http\Controllers\CalendarController::class, 'actions']);
    // Company page used by sidebar
    Route::get('company', [\App\Http\Controllers\Settings\CompanyController::class, 'edit'])->name('company.edit');
    Route::post('company', [\App\Http\Controllers\Settings\CompanyController::class, 'update'])->name('company.update');

    // Activity Logs
    Route::get('logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('logs.index');
    Route::get('logs/list', [\App\Http\Controllers\ActivityLogController::class, 'list']);
    Route::post('logs/generate-test', [\App\Http\Controllers\ActivityLogController::class, 'generateTest'])->name('logs.generate-test');

    // Permissions
    Route::get('permissions', [\App\Http\Controllers\PermissionController::class, 'index'])->name('permissions.index');
    Route::get('permissions/list', [\App\Http\Controllers\PermissionController::class, 'list']);
    Route::post('permissions', [\App\Http\Controllers\PermissionController::class, 'store'])->name('permissions.store');
    Route::put('permissions/{role}', [\App\Http\Controllers\PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('permissions/{role}', [\App\Http\Controllers\PermissionController::class, 'destroy'])->name('permissions.destroy');
});
