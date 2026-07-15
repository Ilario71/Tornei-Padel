<?php
/**
 * Sorteggio ottavi stile UEFA: 1º girone X vs 2º girone accoppiato.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Champions_League_Knockout_Service
{
    /**
     * @throws RuntimeException
     */
    public static function rebuild_from_standings(int $tournament_id): void
    {
        $torneo = TP_Tournament::find($tournament_id);
        if ($torneo === null) {
            throw new RuntimeException(__('Torneo non trovato.', 'tornei-padel'));
        }
        if (! TP_Champions_Padel_Service::is_champions_padel($torneo)) {
            throw new RuntimeException(__('Sorteggio Champions League disponibile solo per tornei Champion\'s Padel.', 'tornei-padel'));
        }

        TP_Match::delete_knockout($tournament_id);

        $groups = TP_Group::for_tournament($tournament_id);
        if (count($groups) !== TP_Champions_Padel_Service::NUM_GROUPS) {
            throw new RuntimeException(
                sprintf(
                    /* translators: %d: number of groups */
                    __('Servono esattamente %d gironi per il tabellone Champion\'s Padel.', 'tornei-padel'),
                    TP_Champions_Padel_Service::NUM_GROUPS
                )
            );
        }

        global $wpdb;
        $table = TP_Database::table('standings');

        $winners = [];
        $runners = [];
        foreach ($groups as $gi => $g) {
            $gid  = (int) $g['id'];
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

            if (! is_array($rows) || count($rows) < TP_Champions_Padel_Service::QUALIFIED_PER_GROUP) {
                $g_name = (string) ($g['name'] ?? ('Girone ' . ($gi + 1)));
                throw new RuntimeException(
                    sprintf(
                        /* translators: %s: group name */
                        __('Classifica incompleta in %s: servono almeno 2 squadre classificate.', 'tornei-padel'),
                        $g_name
                    )
                );
            }

            $winners[$gi] = (int) $rows[0]['team_id'];
            $runners[$gi] = (int) $rows[1]['team_id'];
        }

        $pairings = self::build_round_of_16_pairings($winners, $runners);
        TP_Knockout_Service::seed_fixed_first_round($tournament_id, $pairings, 'gold');
        TP_Knockout_Service::fix_premature_round_closures($tournament_id);
        TP_Knockout_Service::assign_knockout_courts_public($tournament_id);
    }

    /**
     * @param list<int> $winners
     * @param list<int> $runners
     * @return list<array{0: int, 1: int}>
     */
    public static function build_round_of_16_pairings(array $winners, array $runners): array
    {
        $n        = TP_Champions_Padel_Service::NUM_GROUPS;
        $pairings = [];
        for ($i = 0; $i < $n; $i++) {
            $runner_gi  = ($i % 2 === 0) ? ($i + 1) % $n : ($i + $n - 1) % $n;
            $pairings[] = [$winners[$i], $runners[$runner_gi]];
        }

        return $pairings;
    }
}
