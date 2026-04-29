<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class PosteSeeder extends Seeder
{
    public function run()
    {
        $postes = [];

        // ============================================
        // ÉTAT-MAJOR DSI (U01)
        // ============================================

        $postes[] = ['intitule' => 'Directeur des Systèmes d\'Information', 'tube_min' => 'GF14', 'tube_max' => 'U1', 'id_unite' => 'U01'];
        $postes[] = ['intitule' => 'Assistant de Direction', 'tube_min' => 'GF08', 'tube_max' => 'GF10', 'id_unite' => 'U01'];
        $postes[] = ['intitule' => 'Chef du Département Système et Réseaux (DSR)', 'tube_min' => 'GF13', 'tube_max' => 'GF15', 'id_unite' => 'U01'];
        $postes[] = ['intitule' => 'Chef du Département Développement et Exploitation des Services (DDES)', 'tube_min' => 'GF13', 'tube_max' => 'GF15', 'id_unite' => 'U01'];
        $postes[] = ['intitule' => 'Secrétaire des Départements', 'tube_min' => 'GF07', 'tube_max' => 'GF09', 'id_unite' => 'U01'];
        $postes[] = ['intitule' => 'Chef de Cellule de Coordination des Applications Métiers', 'tube_min' => 'GF13', 'tube_max' => 'GF15', 'id_unite' => 'U01'];
        $postes[] = ['intitule' => 'Chef du Service Projets, Urbanisation et Sécurité (SPUS)', 'tube_min' => 'GF11', 'tube_max' => 'GF14', 'id_unite' => 'U01'];
        $postes[] = ['intitule' => 'Chef du Service Archives et Documentation', 'tube_min' => 'GF11', 'tube_max' => 'GF14', 'id_unite' => 'U01'];
        $postes[] = ['intitule' => 'Chef d\'Unité Administration, Gestion et Logistique (UAGL)', 'tube_min' => 'GF09', 'tube_max' => 'GF12', 'id_unite' => 'U01'];

        // ============================================
        // SERVICE SUPERVISION, HELPDESK ET SUPPORT (U08)
        // ============================================
        $postes[] = ['intitule' => 'Chef du Service Supervision, Helpdesk et Support', 'tube_min' => 'GF11', 'tube_max' => 'GF14', 'id_unite' => 'U08'];
        $postes[] = ['intitule' => 'Opérateur Superviseur Réseaux', 'tube_min' => 'GF09', 'tube_max' => 'GF11', 'id_unite' => 'U08'];
        $postes[] = ['intitule' => 'Agent Technique Helpdesk', 'tube_min' => 'GF05', 'tube_max' => 'GF07', 'id_unite' => 'U08'];
        $postes[] = ['intitule' => 'Chef d\'Unité Support Utilisateurs, Maintenance et Réparation Informatique', 'tube_min' => 'GF09', 'tube_max' => 'GF12', 'id_unite' => 'U08'];
        $postes[] = ['intitule' => 'Chef d\'Equipe Support et Maintenance', 'tube_min' => 'GF06', 'tube_max' => 'GF09', 'id_unite' => 'U08'];
        $postes[] = ['intitule' => 'Technicien Informatique', 'tube_min' => 'GF05', 'tube_max' => 'GF07', 'id_unite' => 'U08'];

        // ============================================
        // SERVICE INFRASTRUCTURES SYSTÈMES (U09)
        // ============================================
        $postes[] = ['intitule' => 'Chef du Service Infrastructures Systèmes', 'tube_min' => 'GF11', 'tube_max' => 'GF14', 'id_unite' => 'U09'];
        $postes[] = ['intitule' => 'Expert Systèmes', 'tube_min' => 'GF11', 'tube_max' => 'GF15', 'id_unite' => 'U09'];
        $postes[] = ['intitule' => 'Ingénieur Systèmes', 'tube_min' => 'GF11', 'tube_max' => 'GF13', 'id_unite' => 'U09'];

        // ============================================
        // SERVICE INFRASTRUCTURES RÉSEAUX (U10)
        // ============================================
        $postes[] = ['intitule' => 'Chef du Service Infrastructures Réseaux', 'tube_min' => 'GF11', 'tube_max' => 'GF14', 'id_unite' => 'U10'];
        $postes[] = ['intitule' => 'Expert Réseaux', 'tube_min' => 'GF11', 'tube_max' => 'GF15', 'id_unite' => 'U10'];
        $postes[] = ['intitule' => 'Ingénieur Réseaux', 'tube_min' => 'GF11', 'tube_max' => 'GF13', 'id_unite' => 'U10'];
        $postes[] = ['intitule' => 'Assistant Technique de Maintenance et Réseaux', 'tube_min' => 'GF08', 'tube_max' => 'GF10', 'id_unite' => 'U10'];
        $postes[] = ['intitule' => 'Chef d\'Unité Téléphonie', 'tube_min' => 'GF09', 'tube_max' => 'GF12', 'id_unite' => 'U10'];
        $postes[] = ['intitule' => 'Préparateur', 'tube_min' => 'GF09', 'tube_max' => 'GF11', 'id_unite' => 'U10'];
        $postes[] = ['intitule' => 'Chauffeur', 'tube_min' => 'GF05', 'tube_max' => 'GF07', 'id_unite' => 'U10'];
        $postes[] = ['intitule' => 'Chef d\'Equipe', 'tube_min' => 'GF06', 'tube_max' => 'GF09', 'id_unite' => 'U10'];
        $postes[] = ['intitule' => 'Agent Technique', 'tube_min' => 'GF05', 'tube_max' => 'GF07', 'id_unite' => 'U10'];
        $postes[] = ['intitule' => 'Chef de Groupe Energie', 'tube_min' => 'GF06', 'tube_max' => 'GF09', 'id_unite' => 'U10'];
        $postes[] = ['intitule' => 'Agent Technique Electricien', 'tube_min' => 'GF05', 'tube_max' => 'GF07', 'id_unite' => 'U10'];

        // ============================================
        // SERVICE PRODUCTION ET GESTION DES DONNÉES (U11)
        // ============================================
        $postes[] = ['intitule' => 'Chef de Service Production et Gestion des Données', 'tube_min' => 'GF11', 'tube_max' => 'GF14', 'id_unite' => 'U11'];
        $postes[] = ['intitule' => 'Expert Administrateur de Base de Données', 'tube_min' => 'GF11', 'tube_max' => 'GF15', 'id_unite' => 'U11'];
        $postes[] = ['intitule' => 'Ingénieur de Gestion des Données', 'tube_min' => 'GF11', 'tube_max' => 'GF13', 'id_unite' => 'U11'];
        $postes[] = ['intitule' => 'Chef d\'Unité Exploitation DPS7000', 'tube_min' => 'GF09', 'tube_max' => 'GF12', 'id_unite' => 'U11'];
        $postes[] = ['intitule' => 'Assistant Exploitation', 'tube_min' => 'GF08', 'tube_max' => 'GF10', 'id_unite' => 'U11'];

        // ============================================
        // SERVICES MÉTIERS (CSM) - U12 à U15
        // ============================================
        $services_metiers = [
            'U12' => 'Service Métier Commercial',
            'U13' => 'Service Métier Technique',
            'U14' => 'Service Métier Finances',
            'U15' => 'Service Métier Comptabilité-RH-Logistique',
        ];

        foreach ($services_metiers as $id_unite => $nom_service) {
            $postes[] = ['intitule' => 'Chef de Service Métiers', 'tube_min' => 'GF11', 'tube_max' => 'GF14', 'id_unite' => $id_unite];
            $postes[] = ['intitule' => 'Expert Métiers', 'tube_min' => 'GF11', 'tube_max' => 'GF15', 'id_unite' => $id_unite];
            $postes[] = ['intitule' => 'Ingénieur Métiers', 'tube_min' => 'GF11', 'tube_max' => 'GF13', 'id_unite' => $id_unite];
        }

        // ============================================
        // CELLULE APPLICATIONS MÉTIERS (U04)
        // ============================================
        $postes[] = ['intitule' => 'Expert des Applications Métiers', 'tube_min' => 'GF11', 'tube_max' => 'GF15', 'id_unite' => 'U04'];

        // ============================================
        // SERVICE PROJETS, URBANISATION ET SÉCURITÉ (U05)
        // ============================================
        $postes[] = ['intitule' => 'Expert Chef de Projet Télécommunication, Réseaux et Sécurité', 'tube_min' => 'GF11', 'tube_max' => 'GF15', 'id_unite' => 'U05'];

        // ============================================
        // SERVICE ARCHIVE ET DOCUMENTATION (U06)
        // ============================================
        $postes[] = ['intitule' => 'Expert Gestion et Diffusion de l\'Information', 'tube_min' => 'GF11', 'tube_max' => 'GF15', 'id_unite' => 'U06'];
        $postes[] = ['intitule' => 'Expert Gestion Electronique de Documents', 'tube_min' => 'GF11', 'tube_max' => 'GF15', 'id_unite' => 'U06'];
        $postes[] = ['intitule' => 'Secrétaire', 'tube_min' => 'GF07', 'tube_max' => 'GF09', 'id_unite' => 'U06'];
        $postes[] = ['intitule' => 'Chef d\'Unité Archives Financières et Comptables', 'tube_min' => 'GF09', 'tube_max' => 'GF12', 'id_unite' => 'U06'];
        $postes[] = ['intitule' => 'Assistant Archiviste', 'tube_min' => 'GF08', 'tube_max' => 'GF10', 'id_unite' => 'U06'];
        $postes[] = ['intitule' => 'Agent Archiviste', 'tube_min' => 'GF05', 'tube_max' => 'GF07', 'id_unite' => 'U06'];
        $postes[] = ['intitule' => 'Assistant Archiviste des Délégations Régionales', 'tube_min' => 'GF08', 'tube_max' => 'GF10', 'id_unite' => 'U06'];
        $postes[] = ['intitule' => 'Assistant Documentaliste', 'tube_min' => 'GF08', 'tube_max' => 'GF10', 'id_unite' => 'U06'];
        $postes[] = ['intitule' => 'Agent Documentaliste', 'tube_min' => 'GF05', 'tube_max' => 'GF07', 'id_unite' => 'U06'];
        $postes[] = ['intitule' => 'Chef d\'Unité Archives Générales', 'tube_min' => 'GF09', 'tube_max' => 'GF12', 'id_unite' => 'U06'];

        // ============================================
        // UNITÉ UAGL (U07)
        // ============================================
        $postes[] = ['intitule' => 'Agent Administratif', 'tube_min' => 'GF05', 'tube_max' => 'GF07', 'id_unite' => 'U07'];

        // Insertion
        foreach ($postes as $poste) {
            DB::table('postes')->insert([
                'id_post' => uniqid('POSTE_'),
                'intitule' => $poste['intitule'],
                'tube_min' => $poste['tube_min'],
                'tube_max' => $poste['tube_max'],
                'id_unite' => $poste['id_unite'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}