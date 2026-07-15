<?php
/**
 * Livelli ELO per badge ranking (range, colore, significato).
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Ranking_Level_Service
{
    /** @return list<array{slug: string, label: string, min: int, max: int, meaning: string}> */
    private static function tiers(): array
    {
        return [
            [
                'slug'    => 'rookie',
                'label'   => 'Rookie',
                'min'     => 800,
                'max'     => 999,
                'meaning' => __('Principianti', 'tornei-padel'),
            ],
            [
                'slug'    => 'bronze',
                'label'   => 'Bronze',
                'min'     => 1000,
                'max'     => 1199,
                'meaning' => __('Prime esperienze', 'tornei-padel'),
            ],
            [
                'slug'    => 'silver',
                'label'   => 'Silver',
                'min'     => 1200,
                'max'     => 1399,
                'meaning' => __('Giocatori intermedi', 'tornei-padel'),
            ],
            [
                'slug'    => 'gold',
                'label'   => 'Gold',
                'min'     => 1400,
                'max'     => 1599,
                'meaning' => __('Buon livello competitivo', 'tornei-padel'),
            ],
            [
                'slug'    => 'platinum',
                'label'   => 'Platinum',
                'min'     => 1600,
                'max'     => 1799,
                'meaning' => __('Giocatori avanzati', 'tornei-padel'),
            ],
            [
                'slug'    => 'elite',
                'label'   => 'Elite',
                'min'     => 1800,
                'max'     => PHP_INT_MAX,
                'meaning' => __('Top players', 'tornei-padel'),
            ],
        ];
    }

    /**
     * @return list<array{slug: string, label: string, min: int, max: int, max_display: int|null, meaning: string, range_label: string}>
     */
    public static function all_tiers(): array
    {
        $out = [];
        foreach (self::tiers() as $tier) {
            $max   = $tier['max'];
            $out[] = [
                'slug'        => $tier['slug'],
                'label'       => $tier['label'],
                'min'         => $tier['min'],
                'max'         => $max,
                'max_display' => $max === PHP_INT_MAX ? null : $max,
                'meaning'     => $tier['meaning'],
                'range_label' => self::format_range_label($tier['min'], $max),
            ];
        }

        return $out;
    }

    /**
     * @return array{slug: string, label: string, min: int, max: int, meaning: string, range_label: string}
     */
    public static function from_elo(int $elo): array
    {
        $tiers = self::tiers();
        if ($elo < $tiers[0]['min']) {
            $tier = $tiers[0];
        } else {
            $tier = $tiers[count($tiers) - 1];
            foreach ($tiers as $t) {
                if ($elo >= $t['min'] && $elo <= $t['max']) {
                    $tier = $t;
                    break;
                }
            }
        }

        return [
            'slug'        => $tier['slug'],
            'label'       => $tier['label'],
            'min'         => $tier['min'],
            'max'         => $tier['max'],
            'meaning'     => $tier['meaning'],
            'range_label' => self::format_range_label($tier['min'], $tier['max']),
        ];
    }

    private static function format_range_label(int $min, int $max): string
    {
        if ($max === PHP_INT_MAX) {
            return $min . '+';
        }

        return $min . '–' . $max;
    }
}
