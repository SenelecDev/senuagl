<?php

use App\Models\Budget\Investissement;

test('calcule un TRI positif par dichotomie', function () {
    $tri = Investissement::calculerTri(1000, [500, 500, 500]);

    expect($tri)->not->toBeNull()
        ->and($tri)->toBeGreaterThan(0.23)
        ->and($tri)->toBeLessThan(0.24);
});

test('calcule un TRI négatif quand le projet ne couvre pas le capital', function () {
    $tri = Investissement::calculerTri(1000, [200, 200, 200]);

    expect($tri)->not->toBeNull()
        ->and($tri)->toBeGreaterThan(-0.23)
        ->and($tri)->toBeLessThan(-0.20);
});

test('calcule le DRCI actualisé en années complètes plus fraction', function () {
    $drci = Investissement::calculerDrci(1000, [500, 500, 500], 0.10);

    expect($drci)->toBeGreaterThan(2.35)
        ->and($drci)->toBeLessThan(2.36);
});

test('formate le DRCI en années mois et jours', function () {
    $libelle = Investissement::formaterDrci(1.3042);

    expect($libelle)->toBe('1 an, 3 mois, 20 jours');
});

test('retourne null quand le capital nest jamais récupéré', function () {
    $drci = Investissement::calculerDrci(1000, [100, 100, 100], 0.10);

    expect($drci)->toBeNull();
});
