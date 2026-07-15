<?php
/**
 * Calcolo classifiche gironi.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Standings_Service
{
    private const PTS_WIN = 3;

    public static function recalculate_tournament(int $tournament_id): void
    {
        foreach (TP_Group::for_tournament($tournament_id) as $group) {
            self::recalculate_group($tournament_id, (int) $group['id']);
        }
    }

    public static function recalculate_group(int $tournament_id, int $group_id): void
    {
        global $wpdb;

        $teams   = TP_Group::teams_in_group($group_id);
        $team_ids = array_map(static fn ($t) => (int) $t['id'], $teams);

        $stats = [];
        foreach ($team_ids as $tid) {
            $stats[$tid] = [
                'points' => 0, 'wins' => 0, 'losses' => 0,
                'sets_won' => 0, 'sets_lost' => 0,
                'games_won' => 0, 'games_lost' => 0,
            ];
        }

        $matches = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . TP_Database::table('matches') . "
                 WHERE tournament_id = %d AND phase = 'group' AND group_id = %d
                 AND status IN ('finished','walkover','retired')",
                $tournament_id,
                $group_id
            ),
            ARRAY_A
        );

        if (! is_array($matches)) {
            $matches = [];
        }

        foreach ($matches as $match) {
            $t1 = (int) $match['team1_id'];
            $t2 = (int) $match['team2_id'];
            if ($t1 === 0 || $t2 === 0) {
                continue;
            }

            $winner = $match['winner_team_id'] !== null ? (int) $match['winner_team_id'] : null;

            if ($match['status'] === 'finished') {
                $sets = TP_Match::sets_for_match((int) $match['id']);
                [$s1, $s2, $g1, $g2] = self::counts_from_sets($sets);
                if ($winner === null) {
                    $winner = self::winner_from_counts($t1, $t2, $s1, $s2);
                }
                if (isset($stats[$t1], $stats[$t2])) {
                    $stats[$t1]['sets_won'] += $s1;
                    $stats[$t1]['sets_lost'] += $s2;
                    $stats[$t2]['sets_won'] += $s2;
                    $stats[$t2]['sets_lost'] += $s1;
                    $stats[$t1]['games_won'] += $g1;
                    $stats[$t1]['games_lost'] += $g2;
                    $stats[$t2]['games_won'] += $g2;
                    $stats[$t2]['games_lost'] += $g1;
                }
            } elseif ($winner !== null) {
                $loser = $winner === $t1 ? $t2 : $t1;
                if (isset($stats[$winner], $stats[$loser])) {
                    $stats[$winner]['sets_won'] += 2;
                    $stats[$winner]['sets_lost'] += 0;
                    $stats[$loser]['sets_won'] += 0;
                    $stats[$loser]['sets_lost'] += 2;
                    $stats[$winner]['games_won'] += 12;
                    $stats[$winner]['games_lost'] += 4;
                    $stats[$loser]['games_won'] += 4;
                    $stats[$loser]['games_lost'] += 12;
                }
            }

            if ($winner !== null && isset($stats[$winner], $stats[$winner === $t1 ? $t2 : $t1])) {
                $loser = $winner === $t1 ? $t2 : $t1;
                $stats[$winner]['wins']++;
                $stats[$winner]['points'] += self::PTS_WIN;
                $stats[$loser]['losses']++;
            }
        }

        $table = TP_Database::table('standings');
        foreach ($team_ids as $tid) {
            $s = $stats[$tid];
            $wpdb->replace(
                $table,
                [
                    'tournament_id' => $tournament_id,
                    'group_id'      => $group_id,
                    'team_id'       => $tid,
                    'points'        => $s['points'],
                    'wins'          => $s['wins'],
                    'losses'        => $s['losses'],
                    'sets_won'      => $s['sets_won'],
                    'sets_lost'     => $s['sets_lost'],
                    'games_won'     => $s['games_won'],
                    'games_lost'    => $s['games_lost'],
                ],
                ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d']
            );
        }

        self::assign_ranks($tournament_id, $group_id);
    }

    /** @return list<array<string, mixed>> */
    public static function for_group(int $group_id): array
    {
        global $wpdb;

        $s = TP_Database::table('standings');
        $t = TP_Database::table('teams');

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT st.*, tm.team_name, tm.player1_name, tm.player2_name
                 FROM {$s} st
                 INNER JOIN {$t} tm ON tm.id = st.team_id
                 WHERE st.group_id = %d
                 ORDER BY st.rank_pos ASC, st.points DESC, (st.sets_won - st.sets_lost) DESC",
                $group_id
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param list<array<string, mixed>> $sets
     * @return array{0:int,1:int,2:int,3:int}
     */
    private static function counts_from_sets(array $sets): array
    {
        $s1 = $s2 = $g1 = $g2 = 0;
        foreach ($sets as $set) {
            $a = (int) $set['team1_score'];
            $b = (int) $set['team2_score'];
            $g1 += $a;
            $g2 += $b;
            if ($a > $b) {
                $s1++;
            } elseif ($b > $a) {
                $s2++;
            }
        }

        return [$s1, $s2, $g1, $g2];
    }

    private static function winner_from_counts(int $t1, int $t2, int $s1, int $s2): ?int
    {
        if ($s1 > $s2) {
            return $t1;
        }
        if ($s2 > $s1) {
            return $t2;
        }

        return null;
    }

    private static function assign_ranks(int $tournament_id, int $group_id): void
    {
        global $wpdb;

        $table = TP_Database::table('standings');
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE tournament_id = %d AND group_id = %d
                 ORDER BY points DESC, (sets_won - sets_lost) DESC, (games_won - games_lost) DESC",
                $tournament_id,
                $group_id
            ),
            ARRAY_A
        );

        if (! is_array($rows)) {
            return;
        }

        $rank = 1;
        foreach ($rows as $row) {
            $wpdb->update(
                $table,
                ['rank_pos' => $rank++],
                ['id' => (int) $row['id']],
                ['%d'],
                ['%d']
            );
        }
    }
}
