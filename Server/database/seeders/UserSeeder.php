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
        $this->createRoles();
        $this->createDepartments();
        $this->createUsers();
        $this->command->info('Base de données initialisée avec succès !');
    }

    private function createRoles(): void
    {
        $roles = [
            ['nom' => 'Admin',           'description' => 'Administrateur système avec tous les droits'],
            ['nom' => 'Directeur RH',    'description' => 'Directeur des Ressources Humaines'],
            ['nom' => 'Responsable RH',  'description' => 'Responsable RH'],
            ['nom' => 'Directeur Unité', 'description' => "Directeur d'unité"],
            ['nom' => 'Superieur',       'description' => 'Supérieur hiérarchique'],
            ['nom' => 'Employe',         'description' => 'Employé'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(['nom' => $roleData['nom']], $roleData);
        }

        $this->command->info('Rôles créés');
    }

    private function createDepartments(): void
    {
        $departments = [
            ['name' => 'Direction Générale', 'code' => 'DIR',   'description' => "Direction générale de l'entreprise", 'budget' => 1000000.00],
            ['name' => 'Ressources Humaines','code' => 'RH',    'description' => 'Département RH',                     'budget' => 500000.00],
            ['name' => 'Informatique',        'code' => 'IT',    'description' => 'Service informatique',               'budget' => 750000.00],
            ['name' => 'Comptabilité',        'code' => 'COMPTA','description' => 'Service comptabilité',               'budget' => 300000.00],
            ['name' => 'Commercial',          'code' => 'COM',   'description' => 'Service commercial',                 'budget' => 600000.00],
        ];

        foreach ($departments as $deptData) {
            Department::firstOrCreate(['code' => $deptData['code']], $deptData);
        }

        $this->command->info('Départements créés');
    }

    private function createUsers(): void
    {
        $adminRole          = Role::where('nom', 'Admin')->first();
        $superieurRole      = Role::where('nom', 'Superieur')->first();
        $employeRole        = Role::where('nom', 'Employe')->first();
        $directeurRhRole    = Role::where('nom', 'Directeur RH')->first();
        $responsableRhRole  = Role::where('nom', 'Responsable RH')->first();
        $directeurUniteRole = Role::where('nom', 'Directeur Unité')->first();

        $directionDept  = Department::where('code', 'DIR')->first();
        $informatiqueDept = Department::where('code', 'IT')->first();
        $rhDept         = Department::where('code', 'RH')->first();
        $commercialDept = Department::where('code', 'COM')->first();

        $users = [
            // Administrateur — insasarr1@gmail.com → Insa Sarr
            [
                'name'          => 'Sarr',
                'first_name'    => 'Insa',
                'matricule'     => 'ADM001',
                'email'         => 'insasarr1@gmail.com',
                'password'      => 'admin',
                'role_id'       => $adminRole->id,
                'department_id' => $directionDept->id,
                'phone'         => '+221771000001',
            ],
            // Directeur RH — iboug670@gmail.com → Ibou Gueye
            [
                'name'          => 'Gueye',
                'first_name'    => 'Ibou',
                'matricule'     => 'DRH001',
                'email'         => 'iboug670@gmail.com',
                'password'      => 'directeur',
                'role_id'       => $directeurRhRole->id,
                'department_id' => $rhDept->id,
                'phone'         => '+221771000002',
            ],
            // Responsable RH — responsable.rh@mail.com → Rokhaya Hann
            [
                'name'          => 'Hann',
                'first_name'    => 'Rokhaya',
                'matricule'     => 'RRH001',
                'email'         => 'responsable.rh@mail.com',
                'password'      => 'responsable',
                'role_id'       => $responsableRhRole->id,
                'department_id' => $rhDept->id,
                'phone'         => '+221771000003',
            ],
            // Directeur Unité — directeur.unite@mail.com → Djibril Unité
            [
                'name'          => 'Diallo',
                'first_name'    => 'Djibril',
                'matricule'     => 'DU001',
                'email'         => 'directeur.unite@mail.com',
                'password'      => 'directeur',
                'role_id'       => $directeurUniteRole->id,
                'department_id' => $commercialDept->id,
                'phone'         => '+221771000004',
            ],
            // Supérieur — alimalaye54@gmail.com → Alima Laye (ou Ali Malaye)
            [
                'name'          => 'Ndiaye',
                'first_name'    => 'Alimalaye',
                'matricule'     => 'SUP001',
                'email'         => 'alimalaye54@gmail.com',
                'password'      => 'superieur',
                'role_id'       => $superieurRole->id,
                'department_id' => $informatiqueDept->id,
                'phone'         => '+221771000005',
            ],
            // Employé — kaousmane3599@gmail.com → Kaoussane (Kaoussane)
            [
                'name'          => 'Ka',
                'first_name'    => 'ousmane',
                'matricule'     => 'EMP001',
                'email'         => 'kaousmane3599@gmail.com',
                'password'      => 'employe',
                'role_id'       => $employeRole->id,
                'department_id' => $informatiqueDept->id,
                'phone'         => '+221771000006',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::where('email', $userData['email'])->first();

            if ($user) {
                // Mettre à jour le nom et prénom si l'utilisateur existe déjà
                $user->update([
                    'name'       => $userData['name'],
                    'first_name' => $userData['first_name'],
                    'matricule'  => $userData['matricule'],
                    'phone'      => $userData['phone'],
                ]);
                $this->command->info("Utilisateur mis à jour : {$userData['email']}");
            } else {
                User::create([
                    'name'             => $userData['name'],
                    'first_name'       => $userData['first_name'],
                    'matricule'        => $userData['matricule'],
                    'email'            => $userData['email'],
                    'password'         => Hash::make($userData['password']),
                    'role_id'          => $userData['role_id'],
                    'department_id'    => $userData['department_id'],
                    'is_active'        => true,
                    'phone'            => $userData['phone'],
                    'email_verified_at'=> now(),
                ]);
                $this->command->info("Utilisateur créé : {$userData['email']} / {$userData['password']}");
            }
        }

        $this->command->info('Tous les utilisateurs ont été traités');
    }
}