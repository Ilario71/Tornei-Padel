<?php
/**
 * Validazione punteggi per regolamento Champion's Padel.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Scoring_Rules_Service
{
    /**
     * @param array<string, mixed>|null $tournament
     * @param list<array{set_number:int, team1_score:int, team2_score:int, is_super_tiebreak?:bool}> $sets
     */
    public static function validate_match_sets(?array $tournament, array $sets): ?string
    {
        if ($tournament === null || ! TP_Champions_Padel_Service::is_champions_padel($tournament)) {
            return null;
        }

        $sets_to_win = max(1, (int) ($tournament['sets_to_win'] ?? 2));
        $played      = [];

        foreach ($sets as $s) {
            $t1  = (int) $s['team1_score'];
            $t2  = (int) $s['team2_score'];
            $stb = ! empty($s['is_super_tiebreak']);
            if ($t1 === 0 && $t2 === 0 && ! $stb) {
                continue;
            }

            $err = self::validate_champions_set($t1, $t2, $stb);
            if ($err !== null) {
                $num = (int) ($s['set_number'] ?? 0);

                return $num > 0
                    ? sprintf(/* translators: 1: set number, 2: error */ __('Set %1$d: %2$s', 'tornei-padel'), $num, $err)
                    : $err;
            }

            if ($t1 === $t2) {
                $num = (int) ($s['set_number'] ?? 0);

                return $num > 0
                    ? sprintf(/* translators: %d: set number */ __('Set %d: il set non può finire in parità.', 'tornei-padel'), $num)
                    : __('Un set non può finire in parità.', 'tornei-padel');
            }

            $played[] = $t1 > $t2 ? 1 : 2;
        }

        if ($played === []) {
            return __('Inserisci almeno un set con punteggio valido.', 'tornei-padel');
        }

        $w1 = count(array_filter($played, static fn (int $w): bool => $w === 1));
        $w2 = count(array_filter($played, static fn (int $w): bool => $w === 2));
        if ($w1 < $sets_to_win && $w2 < $sets_to_win) {
            return sprintf(
                /* translators: 1: sets to win, 2: max sets */
                __('Per chiudere il match servono almeno %1$d set vinti (al meglio dei %2$d set).', 'tornei-padel'),
                $sets_to_win,
                $sets_to_win * 2 - 1
            );
        }

        return null;
    }

    public static function validate_champions_set(int $t1, int $t2, bool $is_super_tiebreak): ?string
    {
        if ($is_super_tiebreak) {
            if ($t1 === $t2) {
                return __('Nel super tie-break non è ammesso il pareggio.', 'tornei-padel');
            }
            $w = max($t1, $t2);
            $l = min($t1, $t2);
            if ($w < 7) {
                return __('Super tie-break: servono almeno 7 punti per chiudere.', 'tornei-padel');
            }
            if ($w - $l < 2) {
                return __('Super tie-break: vantaggio minimo di 2 punti (es. 8-6, 9-7).', 'tornei-padel');
            }

            return null;
        }

        if ($t1 === $t2) {
            return __('A parità attiva il super tie-break (STB) e inserisci i punti del tie-break.', 'tornei-padel');
        }

        $w = max($t1, $t2);
        $l = min($t1, $t2);

        if ($t1 === 6 && $t2 === 6) {
            return __('A 6-6 attiva il super tie-break (STB) e registra i punti nel tie-break.', 'tornei-padel');
        }

        if ($w < 6) {
            return __('Set non valido: servono almeno 6 game per chiudere.', 'tornei-padel');
        }

        if ($w === 6 && $l <= 4) {
            return null;
        }

        if ($w === 7 && $l === 5) {
            return null;
        }

        if ($w === 7 && $l === 6) {
            return __('Il 7-6 si gioca con super tie-break a 6-6: attiva STB e inserisci i punti.', 'tornei-padel');
        }

        if ($w > 7 || $l > 6) {
            return __('Punteggio set non previsto dal regolamento Champion\'s Padel.', 'tornei-padel');
        }

        return __('Punteggio set non valido (6-0 … 6-4, 7-5, oppure 6-6 con STB).', 'tornei-padel');
    }
}
