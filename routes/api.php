<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\ParentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques (non authentifiées)
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/register', [AuthController::class, 'register'])->name('api.register');

// Routes protégées (nécessitent une authentification)
Route::middleware(['auth:sanctum'])->group(function () {

    // Routes d'authentification
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    // Routes communes (accessibles aux deux rôles)
    Route::get('/ecoles', [ParentController::class, 'getAllEcoles'])->name('api.ecoles');
    Route::get('/classes-by-ecole/{ecole_id}', [ParentController::class, 'getClassesByEcole'])->name('api.classes.by.ecole');
    Route::get('/all-classes', [SuperAdminController::class, 'getClasses'])->name('api.all.classes');

    // ==================== ROUTES SUPER ADMIN ====================
    Route::middleware(['superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {

        // Dashboard
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

        // Gestion des écoles
        Route::prefix('ecoles')->name('ecoles.')->group(function () {
            Route::get('/', [SuperAdminController::class, 'getEcoles'])->name('index');
            Route::post('/', [SuperAdminController::class, 'storeEcole'])->name('store');
            Route::put('/{id}', [SuperAdminController::class, 'updateEcole'])->name('update');
            Route::delete('/{id}', [SuperAdminController::class, 'deleteEcole'])->name('delete');
        });

        // Gestion des classes
        Route::prefix('classes')->name('classes.')->group(function () {
            Route::get('/', [SuperAdminController::class, 'getClasses'])->name('index');
            Route::post('/', [SuperAdminController::class, 'storeClasse'])->name('store');
            Route::put('/{id}', [SuperAdminController::class, 'updateClasse'])->name('update');
            Route::delete('/{id}', [SuperAdminController::class, 'deleteClasse'])->name('delete');
        });

        // Gestion des utilisateurs
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [SuperAdminController::class, 'getUsers'])->name('index');
            Route::post('/', [SuperAdminController::class, 'storeUser'])->name('store');
            Route::put('/{id}', [SuperAdminController::class, 'updateUser'])->name('update');
            Route::delete('/{id}', [SuperAdminController::class, 'deleteUser'])->name('delete');
        });

        // Gestion des paiements
        Route::prefix('paiements')->name('paiements.')->group(function () {
            Route::get('/', [SuperAdminController::class, 'getPaiements'])->name('index');
            Route::put('/{id}/statut', [SuperAdminController::class, 'updatePaiementStatut'])->name('update.statut');
        });
    });

    // ==================== ROUTES PARENT ====================
    Route::middleware(['parent'])->prefix('parent')->name('parent.')->group(function () {

        // Gestion des élèves
        Route::prefix('eleves')->name('eleves.')->group(function () {
            Route::get('/', [ParentController::class, 'getMesEleves'])->name('index');
            Route::post('/', [ParentController::class, 'addEleve'])->name('store');
            Route::put('/{id}', [ParentController::class, 'updateEleve'])->name('update');
            Route::delete('/{id}', [ParentController::class, 'deleteEleve'])->name('delete');
        });

        // Gestion des paiements
        Route::prefix('paiements')->name('paiements.')->group(function () {
            Route::post('/payer', [ParentController::class, 'makePaiement'])->name('make');
            Route::get('/historique', [ParentController::class, 'mesPaiements'])->name('history');
        });

        // Route pour la vérification du token parent
        Route::get('/verify', function () {
            return response()->json(['message' => 'Token parent valide']);
        })->name('verify');
    });

    // Route de vérification du token (pour débogage)
    Route::get('/verify-token', function () {
        return response()->json([
            'authenticated' => true,
            'user' => auth::user(),
            'role' => auth::user()?->role
        ]);
    })->name('verify.token');
});

// Fallback pour les routes non trouvées
Route::fallback(function () {
    return response()->json([
        'message' => 'Route non trouvée',
        'success' => false
    ], 404);
});
