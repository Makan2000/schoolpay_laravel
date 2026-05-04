<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ecole;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    // Dashboard
    public function dashboard()
    {
        return response()->json([
            'total_ecoles' => Ecole::count(),
            'total_classes' => Classe::count(),
            'total_eleves' => Eleve::count(),
            'total_parents' => User::where('role', 'parent')->count(),
            'total_paiements' => Paiement::count(),
            'montant_total' => Paiement::where('statut', 'valide')->sum('montant'),
            'recent_paiements' => Paiement::with(['parent', 'eleve', 'ecole'])->latest()->take(5)->get(),
            'recent_ecoles' => Ecole::latest()->take(5)->get(),
            'stats_par_mois' => $this->getMonthlyStats(),
        ]);
    }

    private function getMonthlyStats()
    {
        $stats = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $stats[] = [
                'mois' => $month->format('M Y'),
                'montant' => Paiement::where('statut', 'valide')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('montant')
            ];
        }
        return $stats;
    }

    // Gestion des écoles
    public function getEcoles()
    {
        return response()->json(Ecole::with('classes')->get());
    }

    public function storeEcole(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:ecoles',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
        ]);

        $ecole = Ecole::create($validated);
        return response()->json($ecole, 201);
    }

    public function updateEcole(Request $request, $id)
    {
        $ecole = Ecole::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('ecoles')->ignore($id)],
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
        ]);

        $ecole->update($validated);
        return response()->json($ecole);
    }

    public function deleteEcole($id)
    {
        $ecole = Ecole::findOrFail($id);
        $ecole->delete();
        return response()->json(['message' => 'École supprimée avec succès']);
    }

    // Gestion des classes
    public function getClasses()
    {
        return response()->json(Classe::with('ecole')->get());
    }

    public function storeClasse(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'ecole_id' => 'required|exists:ecoles,id'
        ]);

        $classe = Classe::create($validated);
        return response()->json($classe, 201);
    }

    public function updateClasse(Request $request, $id)
    {
        $classe = Classe::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'ecole_id' => 'required|exists:ecoles,id'
        ]);

        $classe->update($validated);
        return response()->json($classe);
    }

    public function deleteClasse($id)
    {
        $classe = Classe::findOrFail($id);
        $classe->delete();
        return response()->json(['message' => 'Classe supprimée avec succès']);
    }

    // Gestion des utilisateurs
    public function getUsers()
    {
        $users = User::with('eleves')->get();
        return response()->json($users);
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:superadmin,parent',
            'telephone' => 'nullable|string|max:20'
        ]);

        $user = User::create([
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'telephone' => $validated['telephone'] ?? null
        ]);

        return response()->json($user, 201);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($id)],
            'role' => 'required|in:superadmin,parent',
            'telephone' => 'nullable|string|max:20'
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);
        return response()->json($user);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'superadmin') {
            return response()->json(['message' => 'Impossible de supprimer un super administrateur'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }

    // Gestion des paiements
    public function getPaiements()
    {
        return response()->json(Paiement::with(['parent', 'eleve', 'ecole', 'classe'])->latest()->get());
    }

    public function updatePaiementStatut(Request $request, $id)
    {
        $validated = $request->validate([
            'statut' => 'required|in:en_attente,valide,echoue',
        ]);

        $paiement = Paiement::findOrFail($id);
        $paiement->update([
            'statut' => $validated['statut'],
        ]);

        return response()->json([
            'message' => 'Statut du paiement mis à jour avec succès',
            'paiement' => $paiement->load(['parent', 'eleve', 'ecole', 'classe']),
        ]);
    }
}
