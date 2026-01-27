<?php

use App\Http\Controllers\ProjectInvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Invitation routes (must be accessible without authentication)
Route::prefix('invitations')->as('invitations.')->group(function () {
    Route::get('/{token}', [ProjectInvitationController::class, 'show'])->name('show');
    Route::post('/{token}/accept', [ProjectInvitationController::class, 'accept'])->name('accept');
    Route::post('/{token}/decline', [ProjectInvitationController::class, 'decline'])->name('decline');
});
