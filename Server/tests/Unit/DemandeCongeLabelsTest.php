<?php

namespace Tests\Unit;

use App\Models\DemandeConge;
use PHPUnit\Framework\TestCase;

class DemandeCongeLabelsTest extends TestCase
{
    /**
     * @dataProvider typeLabelProvider
     */
    public function test_type_demande_label_is_human_readable(string $type, string $expectedLabel): void
    {
        $demande = new DemandeConge(['type_demande' => $type]);

        $this->assertSame($expectedLabel, $demande->type_label);
    }

    /**
     * @dataProvider statutLabelProvider
     */
    public function test_statut_label_is_human_readable(string $statut, string $expectedLabel): void
    {
        $demande = new DemandeConge(['statut' => $statut]);

        $this->assertSame($expectedLabel, $demande->statut_label);
    }

    public static function typeLabelProvider(): array
    {
        return [
            ['conge_annuel', 'Congé annuel'],
            ['conge_maladie', 'Congé maladie'],
            ['conge_maternite', 'Congé maternité'],
            ['conge_paternite', 'Congé paternité'],
            ['conge_sans_solde', 'Congé sans solde'],
            ['absence_exceptionnelle', 'Absence exceptionnelle'],
            ['report_conge', 'Report de congé'],
        ];
    }

    public static function statutLabelProvider(): array
    {
        return [
            ['en_attente', 'En attente'],
            ['approuve', 'Approuvé'],
            ['rejete', 'Rejeté'],
            ['annule', 'Annulé'],
        ];
    }
}
