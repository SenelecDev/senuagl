<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run()
    {
        $agents = [
            ['matricule' => 'C00557', 'prenom' => 'El Hadji Malick Sy', 'nom' => 'DIOP', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Chef de Département'],
            ['matricule' => 'C00560', 'prenom' => 'Abdoulaye', 'nom' => 'NDAO', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Chef de Service'],
            ['matricule' => 'C00572', 'prenom' => 'Jean Jonas A.', 'nom' => 'DIATTA', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Expert'],
            ['matricule' => 'C00587', 'prenom' => 'Nafissatou', 'nom' => 'DIAGNE', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Chef de Service'],
            ['matricule' => 'C00589', 'prenom' => 'Natou', 'nom' => 'CISSE', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Chef de Service'],
            ['matricule' => 'C00633', 'prenom' => 'Moctar Beïdari', 'nom' => 'TOURE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Chef de Service'],
            ['matricule' => 'C00641', 'prenom' => 'Béatrice Maan', 'nom' => 'NGOM', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Chef de Service'],
            ['matricule' => 'C00642', 'prenom' => 'Géo Léonard', 'nom' => 'NIOUKY', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Chef de Département'],
            ['matricule' => 'C00662', 'prenom' => 'Ndèye Coumba', 'nom' => 'DIOUF', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Expert'],
            ['matricule' => 'C00703', 'prenom' => 'Seydina Oumar', 'nom' => 'NDIAYE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Chef de Service'],
            ['matricule' => 'C00754', 'prenom' => 'Mouhameth Bachir Sy', 'nom' => 'NDIAYE', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Chef de Département'],
            ['matricule' => 'C00772', 'prenom' => 'Ngone Issa', 'nom' => 'SALL', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Expert'],
            ['matricule' => 'C00819', 'prenom' => 'Baba Sény Ben', 'nom' => 'BADJI', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Expert'],
            ['matricule' => 'C00821', 'prenom' => 'Fabeye', 'nom' => 'NDIAYE', 'lieu' => 'HANN', 'fonction' => 'Chef de Service'],
            ['matricule' => 'C00853', 'prenom' => 'Soulé', 'nom' => 'GUEYE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'DSI'],
            ['matricule' => 'C00857', 'prenom' => 'Malick', 'nom' => 'MBAYE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Expert'],
            ['matricule' => 'C00867', 'prenom' => 'THIERNO', 'nom' => 'THIOBANE', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Chef de Service'],
            ['matricule' => 'C00874', 'prenom' => 'EL HADJI SASSOUNA', 'nom' => 'DRAME', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Expert'],
            ['matricule' => 'C00917', 'prenom' => 'MAMADOU', 'nom' => 'SENE', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Ingénieur'],
            ['matricule' => 'C00964', 'prenom' => 'Oumar', 'nom' => 'DIOP', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Chef de Service'],
            ['matricule' => 'C00967', 'prenom' => 'MAGUETTE', 'nom' => 'DIOP', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Ingénieur'],
            ['matricule' => 'C00974', 'prenom' => 'Bara', 'nom' => 'FALL', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Expert'],
            ['matricule' => 'C01010', 'prenom' => 'AMADOU NAR', 'nom' => 'DIOP', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Ingénieur'],
            ['matricule' => 'C01014', 'prenom' => 'AMINATA MARIETOU', 'nom' => 'DIOP', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Ingénieur'],
            ['matricule' => 'C01016', 'prenom' => 'Pape Alioune', 'nom' => 'DIOP', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Chef de Service'],
            ['matricule' => 'C01017', 'prenom' => 'Pape Cheikh Yande', 'nom' => 'NDIAYE', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Ingénieur'],
            ['matricule' => 'C01116', 'prenom' => 'Oumar', 'nom' => 'DIOP', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Expert'],
            ['matricule' => 'C01125', 'prenom' => 'Fatou Laye', 'nom' => 'MBAYE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Expert'],
            ['matricule' => 'C01156', 'prenom' => 'Babacar', 'nom' => 'SECK', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Ingénieur'],
            ['matricule' => 'C01180', 'prenom' => 'Natou', 'nom' => 'CISSE', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Expert'],
            ['matricule' => 'C01186', 'prenom' => 'Abdoulaye', 'nom' => 'GOUMBALA', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Ingénieur'],
            ['matricule' => 'C01207', 'prenom' => 'Cheick', 'nom' => 'GUEYE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Ingénieur'],
            ['matricule' => 'C01275', 'prenom' => 'Alioune Badara', 'nom' => 'NGOM', 'lieu' => 'Mbao Dispatching', 'fonction' => 'Opérateur Réseaux Télécom'],
            ['matricule' => 'C01276', 'prenom' => 'Moctar', 'nom' => 'SOULEYMAN', 'lieu' => 'Mbao Dispatching', 'fonction' => 'Opérateur Réseaux Télécom'],
            ['matricule' => 'C01277', 'prenom' => 'Mamadou', 'nom' => 'BATHILY', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Opérateur Réseaux Télécom'],
            ['matricule' => 'C01278', 'prenom' => 'Oumar Ngalla', 'nom' => 'Samb', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Opérateur Réseaux Télécom'],
            ['matricule' => 'C01288', 'prenom' => 'Papa Mody', 'nom' => 'CISSE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Expert'],
            ['matricule' => 'C01292', 'prenom' => 'Ibrahima', 'nom' => 'DIAW', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Ingénieur'],
            ['matricule' => 'C01331', 'prenom' => 'Serigne Ahmadou M.', 'nom' => 'FALL', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Ingénieur'],
            ['matricule' => 'C01356', 'prenom' => 'Madeleine', 'nom' => 'SARR', 'lieu' => 'HANN', 'fonction' => 'Expert GED'],
            ['matricule' => 'C01357', 'prenom' => 'Malick', 'nom' => 'SEYE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Expert'],
            ['matricule' => 'M04863', 'prenom' => 'OUSSEYNOU', 'nom' => 'NGOM', 'lieu' => 'Diame Niadio', 'fonction' => 'Opérateur Superviseur'],
            ['matricule' => 'M04954', 'prenom' => 'MALICK', 'nom' => 'SY', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Agent Administratif'],
            ['matricule' => 'M05260', 'prenom' => 'IDRISSA', 'nom' => 'DIEYE', 'lieu' => 'Diame Niadio', 'fonction' => 'Opérateur Superviseur'],
            ['matricule' => 'M05324', 'prenom' => 'Amadou Assane', 'nom' => 'Cissé', 'lieu' => 'Mbao Dispatching', 'fonction' => 'Chef d\'Unité'],
            ['matricule' => 'M05546', 'prenom' => 'Alassane', 'nom' => 'COLY', 'lieu' => 'Diame Niadio', 'fonction' => 'Opérateur Superviseur'],
            ['matricule' => 'M05570', 'prenom' => 'Awa', 'nom' => 'DIOP', 'lieu' => 'Mbao Dispatching', 'fonction' => 'Assistant Technique'],
            ['matricule' => 'M05588', 'prenom' => 'ADAMA', 'nom' => 'DIOP', 'lieu' => 'HANN', 'fonction' => 'Chef d\'Unité'],
            ['matricule' => 'M05595', 'prenom' => 'Marème', 'nom' => 'NIANG', 'lieu' => 'Rufisque', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M05768', 'prenom' => 'Papa Djibril', 'nom' => 'TOURE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Aide-Archiviste'],
            ['matricule' => 'M05783', 'prenom' => 'Mame Fatou Binta', 'nom' => 'SAMB', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Assistante de Direction'],
            ['matricule' => 'M05785', 'prenom' => 'Aïssatou', 'nom' => 'SECK', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Chef d\'Equipe'],
            ['matricule' => 'M05810', 'prenom' => 'Ramatoulaye', 'nom' => 'LY', 'lieu' => 'HANN', 'fonction' => 'Aide Documentaliste'],
            ['matricule' => 'M05819', 'prenom' => 'Jean Samnack', 'nom' => 'CISS', 'lieu' => 'Diame Niadio', 'fonction' => 'Assistant'],
            ['matricule' => 'M05820', 'prenom' => 'Ndèye Aminata M.', 'nom' => 'SIDIBE', 'lieu' => 'Guédiawaye', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M05911', 'prenom' => 'Oumar', 'nom' => 'CISSE', 'lieu' => 'Diame Niadio', 'fonction' => 'Assistant'],
            ['matricule' => 'M06074', 'prenom' => 'Moussa', 'nom' => 'DIENE', 'lieu' => 'Diame Niadio', 'fonction' => 'Opérateur Superviseur'],
            ['matricule' => 'M06084', 'prenom' => 'Pape Amara', 'nom' => 'CISSE', 'lieu' => 'Mbao', 'fonction' => 'Assistant Technique'],
            ['matricule' => 'M06267', 'prenom' => 'Assane', 'nom' => 'SY', 'lieu' => 'Saint Louis', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M06303', 'prenom' => 'Papa Assane', 'nom' => 'GUEYE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Chef d\'Unité'],
            ['matricule' => 'M06373', 'prenom' => 'CHERIF MOUHAMADOU', 'nom' => 'SOW', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Assistant'],
            ['matricule' => 'M06374', 'prenom' => 'TAFSIR', 'nom' => 'DIOP', 'lieu' => 'Mbao Dispatching', 'fonction' => 'Assistant'],
            ['matricule' => 'M06377', 'prenom' => 'ADOLF EUDES', 'nom' => 'NZALE', 'lieu' => 'HANN', 'fonction' => 'Chef d\'Unité'],
            ['matricule' => 'M06455', 'prenom' => 'Ousmane Camara', 'nom' => 'CISSE', 'lieu' => 'Diame Niadio', 'fonction' => 'Assistant'],
            ['matricule' => 'M06472', 'prenom' => 'Moustapha', 'nom' => 'FALL', 'lieu' => 'Mbao Dispatching', 'fonction' => 'Assistant Technique'],
            ['matricule' => 'M06591', 'prenom' => 'Sokhna', 'nom' => 'NIANG', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Chef d\'Unité'],
            ['matricule' => 'M06722', 'prenom' => 'Tidiane', 'nom' => 'SY', 'lieu' => 'Cité Keur Gorgui', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M07004', 'prenom' => 'Amadou Moustapha', 'nom' => 'DIAW', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Agent Technique'],
            ['matricule' => 'M07086', 'prenom' => 'Nafissatou', 'nom' => 'MBAYE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M07476', 'prenom' => 'Mamadou', 'nom' => 'SARR', 'lieu' => 'Ziguinchor', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M07477', 'prenom' => 'Aba', 'nom' => 'COLY', 'lieu' => 'Cap des Biches/CFPP', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M07570', 'prenom' => 'Fatou', 'nom' => 'NDIAYE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Agent Helpdesk'],
            ['matricule' => 'M07683', 'prenom' => 'Ndèye Diakhaté', 'nom' => 'SARR', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Secrétaire'],
            ['matricule' => 'M07685', 'prenom' => 'Souleymane', 'nom' => 'KANE', 'lieu' => 'Mbao Dispatching', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M07843', 'prenom' => 'Matar', 'nom' => 'DIAKHATE', 'lieu' => 'Guédiawaye', 'fonction' => 'Technicien de Maintenance Informatique'],
            ['matricule' => 'M07881', 'prenom' => 'Barro', 'nom' => 'LAME', 'lieu' => 'Thiès', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M07890', 'prenom' => 'Ameth', 'nom' => 'DJIGO', 'lieu' => 'Patte d\'Oie', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M07927', 'prenom' => 'EL Hadji Baka', 'nom' => 'CISSE', 'lieu' => 'Kaolack', 'fonction' => 'Chef de Groupe'],
            ['matricule' => 'M08111', 'prenom' => 'Mamadou Moustapha', 'nom' => 'GASSAMA', 'lieu' => 'Patte d\'Oie', 'fonction' => 'Technicien de Maintenance Informatique'],
            ['matricule' => 'M08183', 'prenom' => 'Moustapha', 'nom' => 'KOITA', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Technicien de Maintenance Informatique'],
            ['matricule' => 'M08521', 'prenom' => 'Ndeye Maguette', 'nom' => 'DIARRA', 'lieu' => 'Rufisque', 'fonction' => 'Technicien de Maintenance Informatique'],
            ['matricule' => 'M08592', 'prenom' => 'Serigne D Mohamed R.', 'nom' => 'SY', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Technicien de Maintenance'],
            ['matricule' => 'M08609', 'prenom' => 'Alioune Dahim', 'nom' => 'GUEYE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Technicien de Maintenance'],
            ['matricule' => 'M08684', 'prenom' => 'Makhtar', 'nom' => 'GOUDIABY', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Agent Documentaliste'],
            ['matricule' => 'M08752', 'prenom' => 'André Bernard Idriss', 'nom' => 'DIOP', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Technicien de Maintenance Informatique'],
            ['matricule' => 'M08753', 'prenom' => 'Bécaye', 'nom' => 'SIDIBE', 'lieu' => 'Siége Social Vincens', 'fonction' => 'Technicien de Maintenance Informatique'],
        ];

        // Nettoyage et insertion
        foreach ($agents as $agent) {
            // Trouver l'ID du poste correspondant à la fonction
            $poste = DB::table('postes')->where('intitule', 'like', '%' . $this->mapFonctionToPoste($agent['fonction']) . '%')->first();
            
            if (!$poste) {
                // Si pas trouvé, on log ou on ignore
                echo "Poste non trouvé pour : " . $agent['fonction'] . "\n";
                continue;
            }
            
            // Dates aléatoires
            $dateNaissance = $this->randomDate('1960-01-01', '1995-12-31');
            $dateEmbauche = $this->randomDate('1985-01-01', '2020-12-31');
            
            // GF aléatoire entre tube_min et tube_max du poste
            $gfMin = DB::table('gfs')->where('id_gf', $poste->tube_min)->first()->ordre;
            $gfMax = DB::table('gfs')->where('id_gf', $poste->tube_max)->first()->ordre;
            $gfOrdre = rand($gfMin, $gfMax);
            $gfActuel = DB::table('gfs')->where('ordre', $gfOrdre)->first()->id_gf;
            
            // NR aléatoire
            $nrOrdre = rand(40, 85);
            $nrActuel = DB::table('nrs')->where('ordre', $nrOrdre)->first()->id_nr;
            
            DB::table('agents')->insert([
                'matricule' => $agent['matricule'],
                'nom' => $agent['nom'],
                'prenom' => $agent['prenom'],
                'sexe' => $this->guessSexe($agent['prenom']),
                'date_naissance' => $dateNaissance,
                'lieu_naissance' => $agent['lieu'],
                'situation_familiale' => null,
                'nombre_enfants' => rand(0, 4),
                'date_embauche' => $dateEmbauche,
                'nationalite' => 'Sénégalaise',
                'id_post' => $poste->id_post,
                'id_gf_actuel' => $gfActuel,
                'id_nr_actuel' => $nrActuel,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    
    private function mapFonctionToPoste($fonction)
    {
        $map = [
            'Chef de Département' => 'Chef du Département',
            'Chef de Service' => 'Chef de Service',
            'Expert' => 'Expert',
            'Ingénieur' => 'Ingénieur',
            'Opérateur Réseaux Télécom' => 'Opérateur Superviseur',
            'Opérateur Superviseur' => 'Opérateur Superviseur',
            'Agent Administratif' => 'Agent Administratif',
            "Chef d'Unité" => 'Chef d\'Unité',
            'Assistant Technique' => 'Assistant Technique',
            'Chef de Groupe' => 'Chef d\'Equipe',
            'Aide-Archiviste' => 'Agent Archiviste',
            'Assistante de Direction' => 'Assistant de Direction',
            "Chef d'Equipe" => 'Chef d\'Equipe',
            'Aide Documentaliste' => 'Agent Documentaliste',
            'Assistant' => 'Assistant',
            'Agent Technique' => 'Agent Technique',
            'Agent Helpdesk' => 'Agent Technique Helpdesk',
            'Secrétaire' => 'Secrétaire',
            'Technicien de Maintenance Informatique' => 'Technicien Informatique',
            'Technicien de Maintenance' => 'Technicien Informatique',
            'Agent Documentaliste' => 'Agent Documentaliste',
            'Expert GED' => 'Expert Gestion Electronique de Documents',
            'DSI' => 'Directeur des Systèmes d\'Information',
        ];
        
        return $map[$fonction] ?? $fonction;
    }
    
    private function guessSexe($prenom)
    {
        // Liste approximative 
        $feminins = ['Nafissatou', 'Natou', 'Béatrice', 'Ndèye', 'Fatou', 'Aminata', 'Marietou', 'Awa', 'Marème', 'Ramatoulaye', 'Ndèye Aminata', 'Sokhna', 'Madeleine', 'Maguette', 'Aissatou'];
        
        foreach ($feminins as $f) {
            if (str_contains($prenom, $f)) {
                return 'F';
            }
        }
        return 'M';
    }
    
    private function randomDate($start, $end)
    {
        return date('Y-m-d', rand(strtotime($start), strtotime($end)));
    }
}