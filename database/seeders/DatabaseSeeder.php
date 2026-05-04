<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Ecole;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Paiement;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Création du Super Admin
        User::create([
            'nom' => 'Super Admin',
            'email' => 'admin@schoolpay.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'telephone' => '690000000'
        ]);

        // Création des écoles
        $ecoles = [
            ['nom' => 'Complexe Scolaire Catholique Bienheureuse Mère Térésa', 'email' => 'mere.teresa@school.com', 'telephone' => '699123456', 'adresse' => 'Papanè'],
            ['nom' => 'Collège Catholique Pierre Joseph de Clorivière', 'email' => 'cloriviere@school.com', 'telephone' => '699789012', 'adresse' => 'Cotonou'],
            ['nom' => 'Université Africain de Développement Coopératif', 'email' => 'uadc@school.com', 'telephone' => '699345678', 'adresse' => 'Parakou'],
            ['nom' => 'OAK INTERNATIONAL SCHOOL', 'email' => 'oak@school.com', 'telephone' => '699456789', 'adresse' => 'Cotonou'],
            ['nom' => 'Complexe Scolaire Catholique La Salle', 'email' => 'lasalle@school.com', 'telephone' => '699567890', 'adresse' => 'Akassato'],
        ];

        foreach ($ecoles as $ecoleData) {
            Ecole::create($ecoleData);
        }

        // Création des classes pour chaque école
        $allEcoles = Ecole::all();
        $classesNames = ['6ème', '5ème', '4ème', '3ème', 'Seconde', 'Première', 'Tle'];

        foreach ($allEcoles as $ecole) {
            foreach ($classesNames as $className) {
                Classe::create([
                    'nom' => $className,
                    'ecole_id' => $ecole->id
                ]);
            }
        }

        // Création des parents
        $parents = [
            ['nom' => 'Jean Dupont', 'email' => 'jean.dupont@email.com', 'password' => Hash::make('parent123'), 'telephone' => '699111222'],
            ['nom' => 'Marie Camara', 'email' => 'marie.camara@email.com', 'password' => Hash::make('parent123'), 'telephone' => '699333444'],
            ['nom' => 'Paul Fokou', 'email' => 'paul.fokou@email.com', 'password' => Hash::make('parent123'), 'telephone' => '699555666'],
        ];

        $createdParents = [];
        foreach ($parents as $parentData) {
            $createdParents[] = User::create($parentData);
        }

        // Création des élèves
        $elevesData = [
            ['nom' => 'Sophie', 'prenom' => 'Dupont', 'parent_index' => 0, 'ecole_index' => 0, 'classe_index' => 0],
            ['nom' => 'Lucas', 'prenom' => 'Dupont', 'parent_index' => 0, 'ecole_index' => 0, 'classe_index' => 1],
            ['nom' => 'Amara', 'prenom' => 'Camara', 'parent_index' => 1, 'ecole_index' => 1, 'classe_index' => 2],
            ['nom' => 'Kadiatou', 'prenom' => 'Camara', 'parent_index' => 1, 'ecole_index' => 1, 'classe_index' => 3],
            ['nom' => 'Kevin', 'prenom' => 'Fokou', 'parent_index' => 2, 'ecole_index' => 2, 'classe_index' => 4],
        ];

        foreach ($elevesData as $eleveData) {
            $classe = Classe::where('ecole_id', $allEcoles[$eleveData['ecole_index']]->id)
                ->skip($eleveData['classe_index'])
                ->first();

            if ($classe) {
                Eleve::create([
                    'nom' => $eleveData['nom'],
                    'prenom' => $eleveData['prenom'],
                    'date_naissance' => fake()->date('Y-m-d', '2010-01-01'),
                    'parent_id' => $createdParents[$eleveData['parent_index']]->id,
                    'ecole_id' => $allEcoles[$eleveData['ecole_index']]->id,
                    'classe_id' => $classe->id
                ]);
            }
        }

        // Création des paiements
        $allEleves = Eleve::all();

        for ($i = 0; $i < 30; $i++) {
            $eleve = $allEleves->random();
            $montant = rand(25000, 150000);

            Paiement::create([
                'parent_id' => $eleve->parent_id,
                'eleve_id' => $eleve->id,
                'ecole_id' => $eleve->ecole_id,
                'classe_id' => $eleve->classe_id,
                'montant' => $montant,
                'statut' => 'valide',
                'reference_transaction' => 'SCHOOLPAY_' . strtoupper(uniqid()) . '_' . time() . '_' . $i,
                'mode_paiement' => rand(0, 1) ? 'mobile_money' : 'carte_bancaire',
                'date_paiement' => now()->subDays(rand(1, 60)),
                'created_at' => now()->subDays(rand(1, 60))
            ]);
        }
    }
}
