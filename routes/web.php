<?php

use App\Http\Controllers\Admin\BasicItemController;
use App\Http\Controllers\Admin\HspController as AdminHspController;
use App\Http\Controllers\Admin\ImportHspController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\User\HspController as UserHspController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [
        LoginController::class,
        'showLoginForm',
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'login',
    ])->name('login.attempt');
});

/*
|--------------------------------------------------------------------------
| Pengguna yang Sudah Login
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/', function (Request $request) {
        $user = $request->user();

        if ($user !== null && $user->role === 'admin') {
            return redirect()->route('admin.hsp.index');
        }

        return redirect()->route('hsp.index');
    })->name('home');

    Route::post('/logout', [
        LoginController::class,
        'logout',
    ])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Tampilan User
    |--------------------------------------------------------------------------
    | User hanya dapat melihat daftar HSP dan detail analisisnya.
    */
    Route::get('/hsp', [
        UserHspController::class,
        'index',
    ])->name('hsp.index');

    Route::get('/hsp/{hsp}', [
        UserHspController::class,
        'show',
    ])->name('hsp.show');

    /*
    |--------------------------------------------------------------------------
    | Tampilan Admin
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('admin')
        ->group(function (): void {
            /*
             * Upah, bahan, dan alat.
             */
            Route::resource(
                'basic-items',
                BasicItemController::class
            )->only([
                'index',
                'edit',
                'update',
            ]);

            /*
             * Import Excel.
             */
            Route::get('/import-excel', [
                ImportHspController::class,
                'index',
            ])->name('import.index');

            Route::post('/import-excel', [
                ImportHspController::class,
                'store',
            ])->name('import.store');

            Route::get('/export/per-analisa', [
                ImportHspController::class,
                'exportPerAnalisa',
            ])->name('export.per-analisa');

            Route::get('/export/menyeluruh', [
                ImportHspController::class,
                'exportMenyeluruh',
            ])->name('export.menyeluruh');

            /*
             * Daftar HSP dan detail analisis AHS.
             *
             * index = daftar pekerjaan
             * show  = detail analisis AHS
             */
            Route::resource(
                'hsp',
                AdminHspController::class
            );
        });
});
