<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer les rôles
        $this->createRoles();
        
        // 2. Créer les départements
        $this->createDepartments();
        
        // 3. Créer les utilisateurs
        $this->createUsers();
        
        $this->command->info('Base de données initialisée avec succès !');
    }

    private function createRoles(): void
    {
        $roles = [
            ['nom' => 'Admin', 'description' => 'Administrateur système avec tous les droits'],
            ['nom' => 'Directeur RH', 'description' => 'Directeur des Ressources Humaines'],
            ['nom' => 'Responsable RH', 'description' => 'Responsable RH'],
            ['nom' => 'Directeur Unité', 'description' => 'Directeur d\'unité'],
            ['nom' => 'Superieur', 'description' => 'Supérieur hiérarchique'],
            ['nom' => 'Employe', 'description' => 'Employé'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(['nom' => $roleData['nom']], $roleData);
        }
        
        $this->command->info('Rôles créés');
    }

    private function createDepartments(): void
    {
        $departments = [
            ['name' => 'Direction Générale', 'code' => 'DIR', 'description' => 'Direction générale de l\'entreprise', 'budget' => 1000000.00],
            ['name' => 'Ressources Humaines', 'code' => 'RH', 'description' => 'Département RH', 'budget' => 500000.00],
            ['name' => 'Informatique', 'code' => 'IT', 'description' => 'Service informatique', 'budget' => 750000.00],
            ['name' => 'Comptabilité', 'code' => 'COMPTA', 'description' => 'Service comptabilité', 'budget' => 300000.00],
            ['name' => 'Commercial', 'code' => 'COM', 'description' => 'Service commercial', 'budget' => 600000.00],
        ];

        foreach ($departments as $deptData) {
            Department::firstOrCreate(['code' => $deptData['code']], $deptData);
        }
        
        $this->command->info('Départements créés');
    }

    private function createUsers(): void
    {
        // Les e-mails ci-dessous sont des exemples : en production, mettez les vraies adresses
        // (ou modifiez chaque compte dans l’admin). Les mails système utilisent le champ `email` en base.

        // Récupérer les rôles et départements
        $adminRole = Role::where('nom', 'Admin')->first();
        $superieurRole = Role::where('nom', 'Superieur')->first();
        $employeRole = Role::where('nom', 'Employe')->first();
        $directeurRhRole = Role::where('nom', 'Directeur RH')->first();
        $responsableRhRole = Role::where('nom', 'Responsable RH')->first();
        $directeurUniteRole = Role::where('nom', 'Directeur Unité')->first();
        
        $directionDept = Department::where('code', 'DIR')->first();
        $informatiqueDept = Department::where('code', 'IT')->first();
        $rhDept = Department::where('code', 'RH')->first();
        $commercialDept = Department::where('code', 'COM')->first();

        $users = [
            // Administrateur
            [
                'name' => 'Admin',
                'first_name' => 'Super',
                'matricule' => 'ADM001',
                'email' => 'insasarr1@gmail.com',
                'password' => 'admin',
                'role_id' => $adminRole->id,
                'department_id' => $directionDept->id,
                'phone' => '+1234567890',
            ],
            // Directeur RH
            [
                'name' => 'Moreau',
                'first_name' => 'Pierre',
                'matricule' => 'DRH002',
                'email' => 'iboug670@gmail.com',
                'password' => 'directeur',
                'role_id' => $directeurRhRole->id,
                'department_id' => $rhDept->id,
                'phone' => '+1234567893',
            ],
            // Responsable RH
            [
                'name' => 'Dubois',
                'first_name' => 'Sophie',
                'matricule' => 'RRH002',
                'email' => 'responsable.rh@mail.com',
                'password' => 'responsable',
                'role_id' => $responsableRhRole->id,
                'department_id' => $rhDept->id,
                'phone' => '+1234567894',
            ],
            // Directeur Unité
            [
                'name' => 'Leblanc',
                'first_name' => 'Michel',
                'matricule' => 'DU001',
                'email' => 'directeur.unite@mail.com',
                'password' => 'directeur',
                'role_id' => $directeurUniteRole->id,
                'department_id' => $commercialDept->id,
                'phone' => '+1234567895',
            ],
            // Supérieur hiérarchique
            [
                'name' => 'Martin',
                'first_name' => 'Jean',
                'matricule' => 'SUP001',
                'email' => 'alimalaye54@gmail.com',
                'password' => 'superieur',
                'role_id' => $superieurRole->id,
                'department_id' => $informatiqueDept->id,
                'phone' => '+1234567891',
            ],
            // Employé
            [
                'name' => 'Dupont',
                'first_name' => 'Marie',
                'matricule' => 'EMP002',
                'email' => 'kaousmane3599@gmail.com',
                'password' => 'employe',
                'role_id' => $employeRole->id,
                'department_id' => $informatiqueDept->id,
                'phone' => '+1234567892',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate([
                'email' => $userData['email']
            ], [
                'name' => $userData['name'],
                'first_name' => $userData['first_name'],
                'matricule' => $userData['matricule'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role_id' => $userData['role_id'],
                'department_id' => $userData['department_id'],
                'is_active' => true,
                'phone' => $userData['phone'],
                'email_verified_at' => now(),
            ]);
            
            $this->command->info("Utilisateur créé: {$userData['email']} / {$userData['password']}");
        }
        
        $this->command->info(' Tous les utilisateurs ont été créés');
    }
}
