<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;

class Investissement extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'montant_initial',
        'taux_actualisation',
        'van',
        'tri',
        'drci',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'montant_initial' => 'decimal:2',
            'taux_actualisation' => 'decimal:6',
            'van' => 'decimal:2',
            'tri' => 'decimal:8',
            'drci' => 'decimal:6',
        ];
    }

    /**
     * @param  array<int, float|int|string>  $recettes
     * @param  array<int, float|int|string>  $charges
     * @return array<int, float>
     */
    public static function calculerFluxNets(array $recettes, array $charges): array
    {
        $flux = [];
        $n = count($recettes);
        for ($i = 0; $i < $n; $i++) {
            $flux[] = (float) $recettes[$i] - (float) $charges[$i];
        }

        return $flux;
    }

    /**
     * Flux nets actualisés (flux de la période t = index + 1).
     *
     * @param  array<int, float>  $fluxNets
     * @return array<int, float>
     */
    public static function calculerFluxNetsActualises(array $fluxNets, float $tauxActualisation): array
    {
        $out = [];
        foreach ($fluxNets as $k => $cf) {
            $t = $k + 1;
            $den = (1.0 + $tauxActualisation) ** $t;
            $out[] = $den > 0 ? $cf / $den : 0.0;
        }

        return $out;
    }

    /**
     * VAN = −I₀ + Σ CFₜ / (1 + r)ᵗ, t = 1…n.
     *
     * @param  array<int, float>  $fluxNets
     */
    public static function calculerVan(float $montantInitial, array $fluxNets, float $tauxActualisation): float
    {
        $van = -$montantInitial;
        foreach ($fluxNets as $k => $cf) {
            $t = $k + 1;
            $van += $cf / (1.0 + $tauxActualisation) ** $t;
        }

        return round($van, 2);
    }

    /**
     * TRI : taux r tel que VAN(r) = 0 (méthode de dichotomie).
     *
     * @param  array<int, float>  $fluxNets
     */
    public static function calculerTri(float $montantInitial, array $fluxNets): ?float
    {
        $npv = function (float $r) use ($montantInitial, $fluxNets): float {
            if ($r <= -1.0) {
                return INF;
            }

            $v = -$montantInitial;
            foreach ($fluxNets as $k => $cf) {
                $t = $k + 1;
                $v += $cf / (1.0 + $r) ** $t;
            }

            return $v;
        };

        $previousRate = -0.9999;
        $previousValue = $npv($previousRate);
        $bracket = null;

        for ($i = 1; $i <= 4000; $i++) {
            $rate = -0.9999 + ($i * (10.9999 / 4000));
            $value = $npv($rate);

            if (! is_finite($previousValue) || ! is_finite($value)) {
                $previousRate = $rate;
                $previousValue = $value;
                continue;
            }

            if (abs($value) < 1e-10) {
                return round($rate, 6);
            }

            if ($previousValue * $value < 0) {
                $bracket = [$previousRate, $rate, $previousValue];
                break;
            }

            $previousRate = $rate;
            $previousValue = $value;
        }

        if ($bracket === null) {
            return null;
        }

        [$low, $high, $fLow] = $bracket;

        for ($i = 0; $i < 200; $i++) {
            $mid = ($low + $high) / 2.0;
            $fMid = $npv($mid);
            if (abs($high - $low) < 1e-8) {
                return round($mid, 6);
            }
            if (abs($fMid) < 1e-10) {
                return round($mid, 6);
            }
            if ($fLow * $fMid < 0) {
                $high = $mid;
                $fHigh = $fMid;
            } else {
                $low = $mid;
                $fLow = $fMid;
            }
        }

        return round(($low + $high) / 2.0, 6);
    }

    /**
     * DRCI : délai (en années, périodes entières + fraction) pour récupérer I₀ avec flux actualisés cumulés.
     *
     * @param  array<int, float>  $fluxNets
     */
    public static function calculerDrci(float $montantInitial, array $fluxNets, float $tauxActualisation): ?float
    {
        if ($montantInitial <= 0) {
            return 0.0;
        }

        if ($tauxActualisation <= -1.0) {
            return null;
        }

        $fluxActualises = self::calculerFluxNetsActualises($fluxNets, $tauxActualisation);
        $cumul = 0.0;
        foreach ($fluxActualises as $k => $fluxActualise) {
            if ($cumul + $fluxActualise >= $montantInitial) {
                $besoin = $montantInitial - $cumul;
                if ($fluxActualise <= 0) {
                    return null;
                }

                $anneesCompletes = $k;
                $fractionAnnee = $besoin / $fluxActualise;

                return round($anneesCompletes + $fractionAnnee, 4);
            }

            $cumul += $fluxActualise;
        }

        return null;
    }

    public static function formaterDrci(?float $drci): ?string
    {
        if ($drci === null) {
            return null;
        }

        $annees = (int) floor($drci);
        $moisDecimal = ($drci - $annees) * 12;
        $mois = (int) floor($moisDecimal);
        $jours = (int) round(($moisDecimal - $mois) * 30);

        if ($jours === 30) {
            $jours = 0;
            $mois++;
        }

        if ($mois === 12) {
            $mois = 0;
            $annees++;
        }

        $parts = [];
        if ($annees > 0) {
            $parts[] = $annees . ' an' . ($annees > 1 ? 's' : '');
        }
        if ($mois > 0) {
            $parts[] = $mois . ' mois';
        }
        if ($jours > 0 || $parts === []) {
            $parts[] = $jours . ' jour' . ($jours > 1 ? 's' : '');
        }

        return implode(', ', $parts);
    }

    /**
     * @param  array<int, float|int|string>  $recettes
     * @param  array<int, float|int|string>  $charges
     * @return array{
     *     flux_nets: array<int, float>,
     *     flux_nets_actualises: array<int, float>,
     *     van: float,
     *     tri: ?float,
     *     drci: ?float,
     *     drci_libelle: ?string
     * }
     */
    public static function calculerIndicateurs(
        float $montantInitial,
        float $tauxActualisation,
        array $recettes,
        array $charges,
    ): array {
        $fluxNets = self::calculerFluxNets($recettes, $charges);
        $fluxActualises = self::calculerFluxNetsActualises($fluxNets, $tauxActualisation);
        $van = self::calculerVan($montantInitial, $fluxNets, $tauxActualisation);
        $tri = self::calculerTri($montantInitial, $fluxNets);
        $drci = self::calculerDrci($montantInitial, $fluxNets, $tauxActualisation);

        return [
            'flux_nets' => $fluxNets,
            'flux_nets_actualises' => $fluxActualises,
            'van' => $van,
            'tri' => $tri,
            'drci' => $drci,
            'drci_libelle' => self::formaterDrci($drci),
        ];
    }

    /**
     * Met à jour les champs VAN, TRI, DRCI à partir de séries recettes / charges.
     *
     * @param  array<int, float|int|string>  $recettes
     * @param  array<int, float|int|string>  $charges
     */
    public function appliquerCalculsEtPersister(array $recettes, array $charges): array
    {
        $resultats = self::calculerIndicateurs(
            (float) $this->montant_initial,
            (float) $this->taux_actualisation,
            $recettes,
            $charges,
        );

        $this->update([
            'van' => $resultats['van'],
            'tri' => $resultats['tri'],
            'drci' => $resultats['drci'],
        ]);

        return $resultats;
    }
}
