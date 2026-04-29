<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class AvancementSeeder extends Seeder
{
    public function run()
    {
        $agents = DB::table('agents')->get();
        $aujourdhui = time();
        
        foreach ($agents as $agent) {
            // Récupérer le poste de l'agent
            $poste = DB::table('postes')->where('id_post', $agent->id_post)->first();
            if (!$poste) continue;
            
            // Récupérer les ordres GF
            $gfMin = DB::table('gfs')->where('id_gf', $poste->tube_min)->first()->ordre;
            $gfMax = DB::table('gfs')->where('id_gf', $poste->tube_max)->first()->ordre;
            $gfActuelOrdre = DB::table('gfs')->where('id_gf', $agent->id_gf_actuel)->first()->ordre;
            
            // Calcul du nombre de promotions possibles (de min à actuel)
            $promotionsPossibles = $gfActuelOrdre - $gfMin;
            
            if ($promotionsPossibles > 0) {
                // On simule entre 1 et 3 promotions selon l'ancienneté
                $nbPromotions = min($promotionsPossibles, rand(1, min(3, $promotionsPossibles)));
                
                // Date d'embauche comme base
                $dateEmbauche = strtotime($agent->date_embauche);
                $intervalle = ($aujourdhui - $dateEmbauche) / ($nbPromotions + 1); // Espacement des promotions
                
                $gfCourantOrdre = $gfMin;
                
                for ($i = 1; $i <= $nbPromotions; $i++) {
                    // Nouveau GF (progression d'au moins 1, au max 2 niveaux)
                    $gfNouveauOrdre = min($gfCourantOrdre + rand(1, 2), $gfActuelOrdre);
                    
                    // Éviter les doublons
                    if ($gfNouveauOrdre <= $gfCourantOrdre) continue;
                    
                    $gfAncien = DB::table('gfs')->where('ordre', $gfCourantOrdre)->first()->id_gf;
                    $gfNouveau = DB::table('gfs')->where('ordre', $gfNouveauOrdre)->first()->id_gf;
                    
                    // Date aléatoire entre embauche et aujourd'hui
                    $datePromo = date('Y-m-d', $dateEmbauche + ($intervalle * $i) + rand(-30, 30));
                    
 
                    DB::table('avancements')->insert([
                        'date' => $datePromo,
                        'matricule_agent' => $agent->matricule,
                        'id_gf_ancien' => $gfAncien,
                        'id_gf_nouveau' => $gfNouveau,
                        'id_nr_ancien' => null,
                        'id_nr_nouveau' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $gfCourantOrdre = $gfNouveauOrdre;
                    
                    // On s'arrête si on a atteint le GF actuel
                    if ($gfCourantOrdre >= $gfActuelOrdre) break;
                }
            }
            
            // Simuler quelques changements NR (indépendants du GF)
            $nbChangementsNR = rand(0, 2);
            $nrActuelOrdre = DB::table('nrs')->where('id_nr', $agent->id_nr_actuel)->first()->ordre;
            $nrMin = 1;
            
            if ($nbChangementsNR > 0 && $nrActuelOrdre > $nrMin) {
                $nrCourantOrdre = $nrMin;
                $dateEmbauche = strtotime($agent->date_embauche);
                $intervalleNR = ($aujourdhui - $dateEmbauche) / ($nbChangementsNR + 1);
                
                for ($i = 1; $i <= $nbChangementsNR; $i++) {
                    $nrNouveauOrdre = min($nrCourantOrdre + rand(1, 2), $nrActuelOrdre);
                    
                    if ($nrNouveauOrdre <= $nrCourantOrdre) continue;
                    
                    $nrAncien = DB::table('nrs')->where('ordre', $nrCourantOrdre)->first()->id_nr;
                    $nrNouveau = DB::table('nrs')->where('ordre', $nrNouveauOrdre)->first()->id_nr;
                    
                    $dateChangement = date('Y-m-d', $dateEmbauche + ($intervalleNR * $i) + rand(-30, 30));
                    
                    DB::table('avancements')->insert([
                        'date' => $dateChangement,
                        'matricule_agent' => $agent->matricule,
                        'id_gf_ancien' => null,
                        'id_gf_nouveau' => null,
                        'id_nr_ancien' => $nrAncien,
                        'id_nr_nouveau' => $nrNouveau,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $nrCourantOrdre = $nrNouveauOrdre;
                    if ($nrCourantOrdre >= $nrActuelOrdre) break;
                }
            }
        }
    }
}