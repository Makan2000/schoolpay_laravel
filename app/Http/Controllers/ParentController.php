<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentController extends Controller
{
    public function getMesEleves()
    {
        $eleves = Eleve::where('parent_id', Auth::id())
            ->with(['ecole', 'classe'])
            ->get();
        return response()->json($eleves);
    }

    public function addEleve(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'date_naissance' => 'nullable|date',
            'ecole_id' => 'required|exists:ecoles,id',
            'classe_id' => 'required|exists:classes,id'
        ]);

        $validated['parent_id'] = Auth::id();

        $eleve = Eleve::create($validated);
        return response()->json($eleve, 201);
    }

    public function updateEleve(Request $request, $id)
    {
        $eleve = Eleve::where('parent_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'date_naissance' => 'nullable|date',
            'ecole_id' => 'required|exists:ecoles,id',
            'classe_id' => 'required|exists:classes,id'
        ]);

        $eleve->update($validated);
        return response()->json($eleve);
    }

    public function deleteEleve($id)
    {
        $eleve = Eleve::where('parent_id', Auth::id())->findOrFail($id);
        $eleve->delete();
        return response()->json(['message' => 'Élève supprimé avec succès']);
    }

    public function makePaiement(Request $request)
    {
        $validated = $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'ecole_id' => 'required|exists:ecoles,id',
            'classe_id' => 'required|exists:classes,id',
            'montant' => 'required|numeric|min:1000|max:10000000'
        ]);

        // Vérifier que l'élève appartient bien au parent
        $eleve = Eleve::where('parent_id', Auth::id())
            ->where('id', $validated['eleve_id'])
            ->firstOrFail();

        $paiement = Paiement::create([
            'parent_id' => Auth::id(),
            'eleve_id' => $validated['eleve_id'],
            'ecole_id' => $validated['ecole_id'],
            'classe_id' => $validated['classe_id'],
            'montant' => $validated['montant'],
            'reference_transaction' => 'SCHOOLPAY_' . strtoupper(uniqid()) . '_' . time(),
            'mode_paiement' => $request->mode_paiement ?? 'mobile_money',
            'date_paiement' => now(),
            'statut' => 'valide'
        ]);

        return response()->json([
            'message' => 'Paiement effectué avec succès',
            'paiement' => $paiement->load(['eleve', 'ecole', 'classe'])
        ], 201);
    }

    public function mesPaiements()
    {
        $paiements = Paiement::where('parent_id', Auth::id())
            ->with(['eleve', 'ecole', 'classe'])
            ->latest()
            ->get();
        return response()->json($paiements);
    }

    public function getAllEcoles()
    {
        return response()->json(\App\Models\Ecole::with('classes')->get());
    }

    public function getClassesByEcole($ecole_id)
    {
        return response()->json(\App\Models\Classe::where('ecole_id', $ecole_id)->get());
    }
}
