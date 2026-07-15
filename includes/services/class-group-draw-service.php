<?php
/**
 * Sorteggio gironi e generazione partite round-robin.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Group_Draw_Service
{
    public static function draw_random(int $tournament_id): void
    {
        $tournament = TP_Tournament::find($tournament_id);
        if ($tournament === null) {
            return;
        }

        if (TP_Champions_Padel_Service::is_champions_padel($tournament)) {
            TP_Champions_Padel_Service::bootstrap_group_phase($tournament_id);

            return;
        }

        $teams      = TP_Team::for_tournament($tournament_id);
        $num_groups = max(1, (int) $tournament['num_groups']);

        TP_Group::clear_for_tournament($tournament_id);
        TP_Match::delete_for_tournament($tournament_id);

        $group_ids = [];
        for ($i = 1; $i <= $num_groups; $i++) {
            $group_ids[] = TP_Group::create($tournament_id, 'Girone ' . self::letter($i), $i);
        }

        shuffle($teams);
        foreach ($teams as $idx => $team) {
            $group_idx = $idx % $num_groups;
            TP_Group::assign_team($group_ids[$group_idx], (int) $team['id']);
        }

        self::generate_group_matches($tournament_id);
    }

    public static function generate_group_matches(int $tournament_id): void
    {
        TP_Activator::ensure_schema();

        $tournament = TP_Tournament::find($tournament_id);
        if ($tournament === null) {
            return;
        }

        TP_Match::delete_for_tournament($tournament_id);

        if (TP_Court::count($tournament_id) === 0) {
            TP_Court::sync_count($tournament_id, 2);
        }

        $pending = [];
        foreach (TP_Group::for_tournament($tournament_id) as $group) {
            $teams = TP_Group::teams_in_group((int) $group['id']);
            $ids   = array_map(static fn ($t) => (int) $t['id'], $teams);
            $pairs = self::round_robin_pairs($ids);

            foreach ($pairs as $pair) {
                $pending[] = [
                    'tournament_id' => $tournament_id,
                    'phase'         => 'group',
                    'group_id'      => (int) $group['id'],
                    'team1_id'      => $pair[0],
                    'team2_id'      => $pair[1],
                    'match_date'    => $tournament['start_date'],
                    'round_name'    => 'Girone ' . $group['name'],
                ];
            }
        }

        $courts     = TP_Court::for_tournament($tournament_id, true);
        $num_courts = count($courts);

        foreach ($pending as $index => $data) {
            if ($num_courts > 0) {
                $data['court_id'] = (int) $courts[$index % $num_courts]['id'];
            }
            TP_Match::create($data);
        }

        TP_Standings_Service::recalculate_tournament($tournament_id);
    }

    /**
     * Sorteggio Champion's Padel: 8 gironi da 4 (Girone A–H).
     */
    public static function draw_champions_groups(int $tournament_id): void
    {
        $tournament = TP_Tournament::find($tournament_id);
        if ($tournament === null) {
            return;
        }

        $teams = TP_Team::for_tournament($tournament_id);
        TP_Group::clear_for_tournament($tournament_id);
        TP_Match::delete_for_tournament($tournament_id);

        $group_ids = [];
        for ($i = 1; $i <= TP_Champions_Padel_Service::NUM_GROUPS; $i++) {
            $group_ids[] = TP_Group::create($tournament_id, 'Girone ' . self::letter($i), $i);
        }

        shuffle($teams);
        foreach ($teams as $idx => $team) {
            $group_idx = $idx % TP_Champions_Padel_Service::NUM_GROUPS;
            TP_Group::assign_team($group_ids[$group_idx], (int) $team['id']);
        }

        self::generate_group_matches($tournament_id);
    }

    /**
     * @param list<int> $team_ids
     * @return list<array{0:int,1:int}>
     */
    private static function round_robin_pairs(array $team_ids): array
    {
        $pairs = [];
        $n     = count($team_ids);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $pairs[] = [$team_ids[$i], $team_ids[$j]];
            }
        }

        return $pairs;
    }

    private static function letter(int $index): string
    {
        return chr(64 + $index);
    }
}
