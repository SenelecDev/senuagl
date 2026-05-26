<?php

use App\Models\Agent;
use App\Models\Poste;
use App\Models\GF;
use App\Models\NR;
use App\Models\Unite;
use App\Models\NoteAppreciation;
use App\Models\Avancement;
use App\Models\User;
use App\Services\PromotionEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed GFs
    $gfs = ['GF05', 'GF06', 'GF07', 'GF08', 'GF09', 'GF10'];
    foreach ($gfs as $i => $code) {
        GF::create(['id_gf' => $code, 'ordre' => $i + 1]);
    }

    // Seed NRs
    for ($i = 1; $i <= 10; $i++) {
        NR::create(['id_nr' => 'NR' . str_pad($i, 2, '0', STR_PAD_LEFT), 'ordre' => $i]);
    }

    // Seed Unite
    Unite::create(['id_unite' => 'U01', 'nom' => 'Direction des Systèmes d\'Information', 'type' => 'Direction']);

    // Seed Poste
    Poste::create([
        'id_post' => 'P01',
        'intitule' => 'Ingénieur Systèmes',
        'tube_min' => 'GF06',
        'tube_max' => 'GF09',
        'id_unite' => 'U01',
    ]);
});

test('un agent eligible est retourne dans la liste de priorite gf', function () {
    // Agent hired in 2020 (more than 3 years ago relative to end of 2024)
    $agent = Agent::create([
        'matricule' => 'C00001',
        'nom' => 'Sow',
        'prenom' => 'Amadou',
        'sexe' => 'M',
        'date_naissance' => '1990-01-01',
        'date_embauche' => '2020-01-01',
        'id_post' => 'P01',
        'id_gf_actuel' => 'GF06',
        'id_nr_actuel' => 'NR01',
    ]);

    // Note for 2024 is 80 (eligible > 75)
    NoteAppreciation::create([
        'matricule_agent' => 'C00001',
        'annee' => 2024,
        'note' => 80,
    ]);

    $service = app(PromotionEligibilityService::class);
    $eligibles = $service->listePrioriteGF('U01', 2024);

    expect($eligibles)->toHaveCount(1);
    expect($eligibles[0]['agent']->matricule)->toBe('C00001');
});

test('un agent plafonne (actuel >= tube_max) n est pas eligible pour promotion gf', function () {
    // Agent current GF is GF09, which is tube_max of P01
    $agent = Agent::create([
        'matricule' => 'C00001',
        'nom' => 'Sow',
        'prenom' => 'Amadou',
        'sexe' => 'M',
        'date_naissance' => '1990-01-01',
        'date_embauche' => '2020-01-01',
        'id_post' => 'P01',
        'id_gf_actuel' => 'GF09',
        'id_nr_actuel' => 'NR01',
    ]);

    NoteAppreciation::create([
        'matricule_agent' => 'C00001',
        'annee' => 2024,
        'note' => 85,
    ]);

    $service = app(PromotionEligibilityService::class);
    $eligibles = $service->listePrioriteGF('U01', 2024);

    expect($eligibles)->toHaveCount(0);
});

test('un agent ayant deja recu un avancement ou promotion cette annee est exclu', function () {
    $agent = Agent::create([
        'matricule' => 'C00001',
        'nom' => 'Sow',
        'prenom' => 'Amadou',
        'sexe' => 'M',
        'date_naissance' => '1990-01-01',
        'date_embauche' => '2020-01-01',
        'id_post' => 'P01',
        'id_gf_actuel' => 'GF06',
        'id_nr_actuel' => 'NR01',
    ]);

    NoteAppreciation::create([
        'matricule_agent' => 'C00001',
        'annee' => 2024,
        'note' => 80,
    ]);

    // Create an avancement in 2024
    Avancement::create([
        'matricule_agent' => 'C00001',
        'id_nr_nouveau' => 'NR02',
        'date' => '2024-06-01',
    ]);

    $service = app(PromotionEligibilityService::class);
    
    // Test GF eligibility
    $eligiblesGf = $service->listePrioriteGF('U01', 2024);
    expect($eligiblesGf)->toHaveCount(0);

    // Test NR eligibility
    $eligiblesNr = $service->listePrioriteNR('U01', 2024);
    expect($eligiblesNr)->toHaveCount(0);
});

test('l API retourne des statistiques de direction avec quotas corrects', function () {
    $user = User::factory()->create();

    // Create 10 agents in the direction to test the 15% / 35% quotas
    for ($i = 1; $i <= 10; $i++) {
        $matricule = 'C000' . str_pad($i, 2, '0', STR_PAD_LEFT);
        Agent::create([
            'matricule' => $matricule,
            'nom' => 'Name' . $i,
            'prenom' => 'First' . $i,
            'sexe' => 'M',
            'date_naissance' => '1990-01-01',
            'date_embauche' => '2020-01-01',
            'id_post' => 'P01',
            'id_gf_actuel' => 'GF06',
            'id_nr_actuel' => 'NR01',
        ]);

        NoteAppreciation::create([
            'matricule_agent' => $matricule,
            'annee' => 2024,
            'note' => 80,
        ]);
    }

    // Promouvoir 1 agent en 2024
    Avancement::create([
        'matricule_agent' => 'C00001',
        'id_gf_nouveau' => 'GF07',
        'date' => '2024-02-01',
    ]);

    $response = $this->actingAs($user)->getJson('/api/promotions/liste-priorite-gf/U01/2024');

    $response->assertOk()
        ->assertJsonPath('stats.total_agents', 10)
        ->assertJsonPath('stats.already_promoted', 1)
        ->assertJsonPath('stats.quota_percent', 15)
        ->assertJsonPath('stats.quota_count', 2); // round(10 * 0.15) = 2
});

test('l enregistrement d un double avancement dans la meme annee est rejete', function () {
    $user = User::factory()->create();

    $agent = Agent::create([
        'matricule' => 'C00001',
        'nom' => 'Sow',
        'prenom' => 'Amadou',
        'sexe' => 'M',
        'date_naissance' => '1990-01-01',
        'date_embauche' => '2020-01-01',
        'id_post' => 'P01',
        'id_gf_actuel' => 'GF06',
        'id_nr_actuel' => 'NR01',
    ]);

    NoteAppreciation::create([
        'matricule_agent' => 'C00001',
        'annee' => 2024,
        'note' => 80,
    ]);

    // First promotion is successful
    $response1 = $this->actingAs($user)->postJson('/api/promotions/promouvoir', [
        'matricule_agent' => 'C00001',
        'id_gf_nouveau' => 'GF07',
        'date' => '2024-02-01',
    ]);
    $response1->assertCreated();

    // Second promotion/avancement in the same year is rejected
    $response2 = $this->actingAs($user)->postJson('/api/promotions/promouvoir', [
        'matricule_agent' => 'C00001',
        'id_gf_nouveau' => 'GF08',
        'date' => '2024-05-01',
    ]);
    $response2->assertStatus(422);
});
