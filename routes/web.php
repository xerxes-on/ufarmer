<?php

use App\Http\Controllers\Admin\SsoController;
use App\Http\Controllers\MediaProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/media/{media}', MediaProxyController::class)->name('media.proxy');

Route::get('/auth/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
Route::get('/auth/callback', [SsoController::class, 'callback'])->name('sso.callback');
