<?php
/**
 * Storico variazioni ELO per match.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Elo_History
{
    public static function exists_for_match(int $match_id): bool
    {
        global $wpdb;

        $id = $wpdb->get_var(
            $wpdb->prepare('SELECT id FROM ' . TP_Database::table('elo_history') . ' WHERE match_id = %d LIMIT 1', $match_id)
        );

        return $id !== null;
    }

    /** @return list<array<string, mixed>> */
    public static function for_match(int $match_id): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . TP_Database::table('elo_history') . ' WHERE match_id = %d ORDER BY id ASC',
                $match_id
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    public static function record(
        int $match_id,
        int $player_id,
        int $before,
        int $after,
        int $delta,
        bool $won
    ): void {
        global $wpdb;

        $wpdb->insert(
            TP_Database::table('elo_history'),
            [
                'match_id'          => $match_id,
                'ranking_player_id' => $player_id,
                'elo_before'        => $before,
                'elo_after'         => $after,
                'elo_delta'         => $delta,
                'won'               => $won ? 1 : 0,
            ],
            ['%d', '%d', '%d', '%d', '%d', '%d']
        );
    }

    public static function revert_match(int $match_id): void
    {
        global $wpdb;

        $rows = self::for_match($match_id);
        foreach ($rows as $row) {
            $pid = (int) $row['ranking_player_id'];
            TP_Ranking_Player::set_elo($pid, (int) $row['elo_before']);
            $won = (int) ($row['won'] ?? 0) === 1;
            $wpdb->query(
                $wpdb->prepare(
                    'UPDATE ' . TP_Database::table('ranking_players') . '
                     SET matches_played = GREATEST(0, matches_played - 1),
                         wins = GREATEST(0, wins - %d),
                         losses = GREATEST(0, losses - %d)
                     WHERE id = %d',
                    $won ? 1 : 0,
                    $won ? 0 : 1,
                    $pid
                )
            );
        }

        $wpdb->delete(TP_Database::table('elo_history'), ['match_id' => $match_id], ['%d']);
    }
}
