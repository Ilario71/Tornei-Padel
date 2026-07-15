<?php
/**
 * Logica risultati partita (parziale vs definitivo).
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Match_Result_Service
{
    /**
     * @return list<array{set_number:int, team1_score:int, team2_score:int}>
     */
    public static function parse_sets_from_post(): array
    {
        $sets = [];
        for ($i = 1; $i <= 3; $i++) {
            $s1_raw = trim((string) ($_POST['set' . $i . '_team1'] ?? ''));
            $s2_raw = trim((string) ($_POST['set' . $i . '_team2'] ?? ''));
            if ($s1_raw === '' && $s2_raw === '') {
                continue;
            }
            $s1 = $s1_raw === '' ? 0 : max(0, min(99, (int) $s1_raw));
            $s2 = $s2_raw === '' ? 0 : max(0, min(99, (int) $s2_raw));
            $stb = ! empty($_POST['set' . $i . '_stb']);
            if ($s1 > 0 || $s2 > 0 || $stb) {
                $sets[] = [
                    'set_number'         => $i,
                    'team1_score'        => $s1,
                    'team2_score'        => $s2,
                    'is_super_tiebreak'  => $stb,
                ];
            }
        }

        return $sets;
    }

    /** @param list<array{set_number:int, team1_score:int, team2_score:int}> $sets */
    public static function sets_have_any_score(array $sets): bool
    {
        return $sets !== [];
    }

    /**
     * @param list<array{set_number:int, team1_score:int, team2_score:int}> $sets
     * @return array{team1:int, team2:int}
     */
    public static function count_sets_won(array $sets): array
    {
        $team1 = $team2 = 0;
        foreach ($sets as $set) {
            if ($set['team1_score'] > $set['team2_score']) {
                $team1++;
            } elseif ($set['team2_score'] > $set['team1_score']) {
                $team2++;
            }
        }

        return ['team1' => $team1, 'team2' => $team2];
    }

    /**
     * @param list<array{set_number:int, team1_score:int, team2_score:int}> $sets
     */
    public static function is_match_complete(array $sets, int $sets_to_win): bool
    {
        $counts = self::count_sets_won($sets);

        return $counts['team1'] >= $sets_to_win || $counts['team2'] >= $sets_to_win;
    }

    /**
     * @param list<array{set_number:int, team1_score:int, team2_score:int}> $sets
     */
    public static function determine_winner(array $match, array $sets): ?int
    {
        $counts = self::count_sets_won($sets);
        if ($counts['team1'] === $counts['team2']) {
            return null;
        }

        return $counts['team1'] > $counts['team2']
            ? (int) $match['team1_id']
            : (int) $match['team2_id'];
    }

    /**
     * @param list<array<string, mixed>> $matches
     * @return list<array<string, mixed>>
     */
    public static function attach_sets(array $matches): array
    {
        foreach ($matches as &$match) {
            $sets = TP_Match::sets_for_match((int) $match['id']);
            $match['sets']       = $sets;
            $match['score_label'] = self::format_score_label($sets);
        }
        unset($match);

        return $matches;
    }

    /**
     * @param list<array<string, mixed>> $sets
     */
    public static function format_score_label(array $sets): string
    {
        if ($sets === []) {
            return '';
        }

        $parts = [];
        foreach ($sets as $set) {
            $parts[] = (int) $set['team1_score'] . '-' . (int) $set['team2_score'];
        }

        return implode(' ', $parts);
    }
}
