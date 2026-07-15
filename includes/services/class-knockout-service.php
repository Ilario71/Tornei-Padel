<?php
/**
 * Tabelloni eliminatori Gold / Silver.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Knockout_Service
{
    public static function supports_brackets(array $tournament): bool
    {
        $formula = (string) ($tournament['formula'] ?? '');

        return $formula === 'groups_knockout' || TP_Champions_Padel_Service::is_champions_padel($tournament);
    }

    public static function next_power_of_two(int $n): int
    {
        return (int) (2 ** (int) ceil(log(max(1, $n), 2)));
    }

    /**
     * @throws RuntimeException
     */
    public static function rebuild_from_standings(int $tournament_id): void
    {
        $tournament = TP_Tournament::find($tournament_id);
        if ($tournament === null) {
            throw new RuntimeException(__('Torneo non trovato.', 'tornei-padel'));
        }

        if (! self::supports_brackets($tournament)) {
            throw new RuntimeException(__('Questo formato torneo non prevede tabelloni eliminatori.', 'tornei-padel'));
        }

        if (TP_Champions_Padel_Service::is_champions_padel($tournament)) {
            TP_Champions_League_Knockout_Service::rebuild_from_standings($tournament_id);

            return;
        }

        TP_Standings_Service::recalculate_tournament($tournament_id);
        TP_Match::delete_knockout($tournament_id);

        $settings  = TP_Tournament::settings($tournament_id);
        $mode      = (string) ($tournament['qualification_mode'] ?? 'gold_only');
        $silver_on = ((int) ($settings['silver_enabled'] ?? 0) === 1) && $mode === 'gold_silver';

        if ($mode === 'gold_silver') {
            $gold = self::teams_by_standings_rank($tournament_id, 2);
            if ($gold === []) {
                throw new RuntimeException(__('Classifiche gironi non disponibili: completa le partite e ricalcola.', 'tornei-padel'));
            }
            self::seed_bracket_matches($tournament_id, $gold, 'gold');

            if ($silver_on) {
                $silver = self::teams_by_standings_positions($tournament_id, [3, 4]);
                if ($silver !== []) {
                    self::seed_bracket_matches($tournament_id, $silver, 'silver');
                }
            }
        } else {
            $top = max(1, (int) ($tournament['qualification_count'] ?? 2));
            $gold = self::teams_by_standings_rank($tournament_id, $top);
            if ($gold === []) {
                throw new RuntimeException(__('Classifiche gironi non disponibili: completa le partite e ricalcola.', 'tornei-padel'));
            }
            self::seed_bracket_matches($tournament_id, $gold, 'gold');
        }

        self::fix_premature_round_closures($tournament_id);
        self::assign_knockout_courts($tournament_id);
    }

    private static function assign_knockout_courts(int $tournament_id): void
    {
        $matches = TP_Match::for_tournament($tournament_id, 'knockout');
        if ($matches === []) {
            return;
        }

        $ids = array_map(static fn (array $m): int => (int) $m['id'], $matches);
        TP_Court::assign_matches_equally($tournament_id, $ids);
    }

    /** @return list<int> */
    private static function teams_by_standings_rank(int $tournament_id, int $top_per_group): array
    {
        global $wpdb;

        $table = TP_Database::table('standings');
        $order = [];

        foreach (TP_Group::for_tournament($tournament_id) as $group) {
            $gid  = (int) $group['id'];
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT team_id FROM {$table}
                     WHERE tournament_id = %d AND group_id = %d
                     ORDER BY rank_pos ASC, points DESC, (sets_won - sets_lost) DESC",
                    $tournament_id,
                    $gid
                ),
                ARRAY_A
            );

            if (! is_array($rows)) {
                continue;
            }

            $picked = array_slice(
                array_map(static fn ($r) => (int) $r['team_id'], $rows),
                0,
                $top_per_group
            );

            foreach ($picked as $tid) {
                if ($tid > 0) {
                    $order[] = $tid;
                }
            }
        }

        return $order;
    }

    /**
     * @param list<int> $positions
     * @return list<int>
     */
    private static function teams_by_standings_positions(int $tournament_id, array $positions): array
    {
        global $wpdb;

        $table = TP_Database::table('standings');
        $out   = [];

        foreach (TP_Group::for_tournament($tournament_id) as $group) {
            $gid  = (int) $group['id'];
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT team_id FROM {$table}
                     WHERE tournament_id = %d AND group_id = %d
                     ORDER BY (rank_pos IS NULL), rank_pos ASC, points DESC, (games_won - games_lost) DESC",
                    $tournament_id,
                    $gid
                ),
                ARRAY_A
            );

            if (! is_array($rows)) {
                continue;
            }

            foreach ($positions as $pos) {
                $idx = $pos - 1;
                if (isset($rows[$idx])) {
                    $tid = (int) $rows[$idx]['team_id'];
                    if ($tid > 0) {
                        $out[] = $tid;
                    }
                }
            }
        }

        return $out;
    }

    /**
     * Tabellone con accoppiamenti fissi al primo turno (es. ottavi Champions League).
     *
     * @param list<array{0: int, 1: int}> $pairings
     */
    public static function seed_fixed_first_round(int $tournament_id, array $pairings, string $bracket_type = 'gold'): void
    {
        if ($pairings === []) {
            return;
        }

        $first_round_matches = count($pairings);
        $bs                  = self::next_power_of_two($first_round_matches * 2);
        $rounds              = (int) round(log($bs, 2));

        foreach ($pairings as $idx => $pair) {
            $slot       = $idx + 1;
            $a          = (int) $pair[0];
            $b          = (int) $pair[1];
            $round_name = self::round_label($first_round_matches);
            $match_id   = TP_Match::create([
                'tournament_id' => $tournament_id,
                'phase'         => 'knockout',
                'bracket_type'  => $bracket_type,
                'team1_id'      => $a > 0 ? $a : null,
                'team2_id'      => $b > 0 ? $b : null,
                'round_index'   => 1,
                'bracket_slot'  => $slot,
                'round_name'    => $round_name . ' (' . ucfirst($bracket_type) . ')',
                'status'        => 'scheduled',
            ]);

            self::resolve_immediate_bye_match($match_id);
        }

        for ($r = 2; $r <= $rounds; $r++) {
            $cnt = (int) ($bs / (2 ** $r));
            for ($slot = 1; $slot <= $cnt; $slot++) {
                TP_Match::create([
                    'tournament_id' => $tournament_id,
                    'phase'         => 'knockout',
                    'bracket_type'  => $bracket_type,
                    'round_index'   => $r,
                    'bracket_slot'  => $slot,
                    'round_name'    => self::round_label($cnt) . ' (' . ucfirst($bracket_type) . ')',
                    'status'        => 'scheduled',
                ]);
            }
        }
    }

    public static function assign_knockout_courts_public(int $tournament_id): void
    {
        self::assign_knockout_courts($tournament_id);
    }

    /** @param list<int> $team_ids */
    private static function seed_bracket_matches(int $tournament_id, array $team_ids, string $bracket_type): void
    {
        if ($team_ids === []) {
            return;
        }

        $bs    = self::next_power_of_two(count($team_ids));
        $teams = $team_ids;
        while (count($teams) < $bs) {
            $teams[] = 0;
        }

        $rounds            = (int) round(log($bs, 2));
        $first_round_count = (int) ($bs / 2);

        for ($slot = 1; $slot <= $first_round_count; $slot++) {
            $a          = (int) $teams[$slot - 1];
            $b          = (int) $teams[$bs - $slot];
            $round_name = self::round_label($first_round_count) . ' (' . ucfirst($bracket_type) . ')';
            $match_id   = TP_Match::create([
                'tournament_id' => $tournament_id,
                'phase'         => 'knockout',
                'bracket_type'  => $bracket_type,
                'team1_id'      => $a > 0 ? $a : null,
                'team2_id'      => $b > 0 ? $b : null,
                'round_index'   => 1,
                'bracket_slot'  => $slot,
                'round_name'    => $round_name,
                'status'        => 'scheduled',
            ]);

            self::resolve_immediate_bye_match($match_id);
        }

        for ($r = 2; $r <= $rounds; $r++) {
            $cnt = (int) ($bs / (2 ** $r));
            for ($slot = 1; $slot <= $cnt; $slot++) {
                TP_Match::create([
                    'tournament_id' => $tournament_id,
                    'phase'         => 'knockout',
                    'bracket_type'  => $bracket_type,
                    'round_index'   => $r,
                    'bracket_slot'  => $slot,
                    'round_name'    => self::round_label($cnt) . ' (' . ucfirst($bracket_type) . ')',
                    'status'        => 'scheduled',
                ]);
            }
        }
    }

    private static function round_label(int $matches_in_round): string
    {
        return match (true) {
            $matches_in_round <= 1 => __('Finale', 'tornei-padel'),
            $matches_in_round === 2 => __('Semifinali', 'tornei-padel'),
            $matches_in_round <= 4 => __('Quarti di finale', 'tornei-padel'),
            $matches_in_round <= 8 => __('Ottavi di finale', 'tornei-padel'),
            default => __('Tabellone', 'tornei-padel'),
        };
    }

    private static function resolve_immediate_bye_match(int $match_id): void
    {
        $m = TP_Match::find($match_id);
        if ($m === null) {
            return;
        }

        $t1 = $m['team1_id'] !== null ? (int) $m['team1_id'] : null;
        $t2 = $m['team2_id'] !== null ? (int) $m['team2_id'] : null;

        if ($t1 !== null && $t2 === null) {
            TP_Match::set_winner($match_id, $t1);
            self::propagate_winner(
                (int) $m['tournament_id'],
                (string) $m['bracket_type'],
                1,
                (int) $m['bracket_slot'],
                $t1
            );
            self::resolve_chain_bye((int) $m['tournament_id'], (string) $m['bracket_type']);
        } elseif ($t2 !== null && $t1 === null) {
            TP_Match::set_winner($match_id, $t2);
            self::propagate_winner(
                (int) $m['tournament_id'],
                (string) $m['bracket_type'],
                1,
                (int) $m['bracket_slot'],
                $t2
            );
            self::resolve_chain_bye((int) $m['tournament_id'], (string) $m['bracket_type']);
        }
    }

    public static function resolve_chain_bye(int $tournament_id, string $bracket_type): void
    {
        global $wpdb;

        $table = TP_Database::table('matches');

        for ($guard = 0; $guard < 32; $guard++) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table}
                     WHERE tournament_id = %d AND phase = 'knockout' AND bracket_type = %s AND status = 'scheduled'",
                    $tournament_id,
                    $bracket_type
                ),
                ARRAY_A
            );

            if (! is_array($rows)) {
                break;
            }

            $found = false;
            foreach ($rows as $m) {
                $round_index = (int) $m['round_index'];
                $bracket_slot = (int) $m['bracket_slot'];
                $t1 = $m['team1_id'] !== null ? (int) $m['team1_id'] : null;
                $t2 = $m['team2_id'] !== null ? (int) $m['team2_id'] : null;

                if ($t1 !== null && $t2 === null) {
                    if (self::is_waiting_for_feeder($tournament_id, $bracket_type, $round_index, $bracket_slot, 2)) {
                        continue;
                    }
                    $found = true;
                    TP_Match::set_winner((int) $m['id'], $t1);
                    self::propagate_winner($tournament_id, $bracket_type, $round_index, $bracket_slot, $t1);
                } elseif ($t2 !== null && $t1 === null) {
                    if (self::is_waiting_for_feeder($tournament_id, $bracket_type, $round_index, $bracket_slot, 1)) {
                        continue;
                    }
                    $found = true;
                    TP_Match::set_winner((int) $m['id'], $t2);
                    self::propagate_winner($tournament_id, $bracket_type, $round_index, $bracket_slot, $t2);
                }
            }

            if (! $found) {
                break;
            }
        }
    }

    public static function fix_premature_round_closures(int $tournament_id): void
    {
        global $wpdb;

        $m_table  = TP_Database::table('matches');
        $ms_table = TP_Database::table('match_sets');

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id FROM {$m_table}
                 WHERE tournament_id = %d AND phase = 'knockout' AND round_index > 1
                 AND status IN ('finished','walkover','retired')
                 AND (team1_id IS NULL OR team2_id IS NULL)",
                $tournament_id
            ),
            ARRAY_A
        );

        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $mid = (int) $row['id'];
            $wpdb->delete($ms_table, ['match_id' => $mid], ['%d']);
            $wpdb->update(
                $m_table,
                ['winner_team_id' => null, 'status' => 'scheduled'],
                ['id' => $mid],
                ['%s', '%s'],
                ['%d']
            );
        }
    }

    /** @param 1|2 $empty_side */
    private static function is_waiting_for_feeder(
        int $tournament_id,
        string $bracket_type,
        int $round_index,
        int $bracket_slot,
        int $empty_side
    ): bool {
        if ($round_index <= 1) {
            return false;
        }

        global $wpdb;

        $feeder_slot = $empty_side === 1 ? (2 * $bracket_slot - 1) : (2 * $bracket_slot);
        $table       = TP_Database::table('matches');

        $feeder = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT status FROM {$table}
                 WHERE tournament_id = %d AND phase = 'knockout' AND bracket_type = %s
                 AND round_index = %d AND bracket_slot = %d LIMIT 1",
                $tournament_id,
                $bracket_type,
                $round_index - 1,
                $feeder_slot
            ),
            ARRAY_A
        );

        if (! is_array($feeder)) {
            return false;
        }

        return ! in_array((string) ($feeder['status'] ?? 'scheduled'), ['finished', 'walkover', 'retired'], true);
    }

    public static function propagate_winner(
        int $tournament_id,
        string $bracket_type,
        int $round_index,
        int $slot,
        int $winner_team_id
    ): void {
        global $wpdb;

        $parent_round = $round_index + 1;
        $parent_slot  = intdiv($slot + 1, 2);
        $field        = ($slot % 2) === 1 ? 'team1_id' : 'team2_id';
        $table        = TP_Database::table('matches');

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET {$field} = %d
                 WHERE tournament_id = %d AND phase = 'knockout' AND bracket_type = %s
                 AND round_index = %d AND bracket_slot = %d",
                $winner_team_id,
                $tournament_id,
                $bracket_type,
                $parent_round,
                $parent_slot
            )
        );
    }

    public static function after_match_resolved(int $match_id): void
    {
        $m = TP_Match::find($match_id);
        if ($m === null || ($m['phase'] ?? '') !== 'knockout' || $m['winner_team_id'] === null) {
            return;
        }

        self::propagate_winner(
            (int) $m['tournament_id'],
            (string) $m['bracket_type'],
            (int) $m['round_index'],
            (int) $m['bracket_slot'],
            (int) $m['winner_team_id']
        );
        self::resolve_chain_bye((int) $m['tournament_id'], (string) $m['bracket_type']);
    }

    /**
     * Anteprima qualificate per l'admin.
     *
     * @return array{gold: list<string>, silver: list<string>}
     */
    public static function preview_qualified(int $tournament_id): array
    {
        $tournament = TP_Tournament::find($tournament_id);
        if ($tournament === null || ! self::supports_brackets($tournament)) {
            return ['gold' => [], 'silver' => []];
        }

        TP_Standings_Service::recalculate_tournament($tournament_id);

        $settings  = TP_Tournament::settings($tournament_id);
        $mode      = (string) ($tournament['qualification_mode'] ?? 'gold_only');
        $silver_on = ((int) ($settings['silver_enabled'] ?? 0) === 1) && $mode === 'gold_silver';

        if ($mode === 'gold_silver') {
            $gold_ids   = self::teams_by_standings_rank($tournament_id, 2);
            $silver_ids = $silver_on ? self::teams_by_standings_positions($tournament_id, [3, 4]) : [];
        } elseif (TP_Champions_Padel_Service::is_champions_padel($tournament)) {
            $gold_ids   = self::teams_by_standings_rank($tournament_id, TP_Champions_Padel_Service::QUALIFIED_PER_GROUP);
            $silver_ids = [];
        } else {
            $gold_ids   = self::teams_by_standings_rank($tournament_id, max(1, (int) ($tournament['qualification_count'] ?? 2)));
            $silver_ids = [];
        }

        return [
            'gold'   => self::team_names($gold_ids),
            'silver' => self::team_names($silver_ids),
        ];
    }

    /** @param list<int> $ids @return list<string> */
    private static function team_names(array $ids): array
    {
        $names = [];
        foreach ($ids as $id) {
            $team = TP_Team::find($id);
            if ($team !== null) {
                $names[] = (string) $team['team_name'];
            }
        }

        return $names;
    }
}
