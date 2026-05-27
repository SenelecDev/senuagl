<?php

use App\Models\Budget\BudgetPrevision;
use App\Models\Budget\Compte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('enregistre une prévision budget', function () {
    $user = User::factory()->create();
    $compte = Compte::query()->create(['numero' => '999001', 'intitule' => 'Compte test']);

    $response = $this->actingAs($user)->postJson('/api/budget', [
        'type' => 'prevision',
        'compte_id' => $compte->id,
        'montant_prevu' => 150_000,
        'annee' => 2024,
        'mois' => 5,
    ]);

    $response->assertCreated()
        ->assertJsonPath('montant_prevu', '150000.00')
        ->assertJsonPath('mois', 5);

    expect(BudgetPrevision::query()->count())->toBe(1);
});

test('met à jour une prévision existante pour le même compte et année', function () {
    $user = User::factory()->create();
    $compte = Compte::query()->create(['numero' => '999002', 'intitule' => 'Compte test']);

    BudgetPrevision::query()->create([
        'compte_id' => $compte->id,
        'montant_prevu' => 100_000,
        'annee' => 2024,
        'mois' => 5,
    ]);

    $response = $this->actingAs($user)->postJson('/api/budget', [
        'type' => 'prevision',
        'compte_id' => $compte->id,
        'montant_prevu' => 150_000,
        'annee' => 2024,
        'mois' => 5,
    ]);

    $response->assertOk()
        ->assertJsonPath('montant_prevu', '150000.00');

    expect(BudgetPrevision::query()->count())->toBe(1);
    expect(BudgetPrevision::query()->first()->montant_prevu)->toBe('150000.00');
});

test('crée une nouvelle prévision pour un mois différent', function () {
    $user = User::factory()->create();
    $compte = Compte::query()->create(['numero' => '999003', 'intitule' => 'Compte test']);

    BudgetPrevision::query()->create([
        'compte_id' => $compte->id,
        'montant_prevu' => 100_000,
        'annee' => 2024,
        'mois' => 5,
    ]);

    $response = $this->actingAs($user)->postJson('/api/budget', [
        'type' => 'prevision',
        'compte_id' => $compte->id,
        'montant_prevu' => 150_000,
        'annee' => 2024,
        'mois' => 6,
    ]);

    $response->assertCreated();

    expect(BudgetPrevision::query()->count())->toBe(2);
});
