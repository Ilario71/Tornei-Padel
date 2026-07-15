<?php
/**
 * Model giocatore ranking ELO.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Ranking_Player
{
    public const START_ELO = 1000;

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . TP_Database::table('ranking_players') . ' WHERE id = %d', $id),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public static function find_by_name_key(string $name_key): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . TP_Database::table('ranking_players') . ' WHERE name_key = %s', $name_key),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public static function find_or_create_by_name(string $display_name): int
    {
        $display = trim($display_name);
        if ($display === '') {
            throw new InvalidArgumentException('Nome giocatore vuoto.');
        }

        $name_key = tp_normalize_player_name_key($display);
        $row      = self::find_by_name_key($name_key);
        if ($row !== null) {
            return (int) $row['id'];
        }

        global $wpdb;

        $inserted = $wpdb->insert(
            TP_Database::table('ranking_players'),
            [
                'user_id'      => null,
                'display_name' => $display,
                'name_key'     => $name_key,
                'elo_rating'   => self::START_ELO,
            ],
            ['%d', '%s', '%s', '%d']
        );

        if ($inserted === false) {
            $existing = self::find_by_name_key($name_key);
            if ($existing !== null) {
                return (int) $existing['id'];
            }

            throw new RuntimeException('Impossibile creare giocatore ranking: ' . (string) $wpdb->last_error);
        }

        return (int) $wpdb->insert_id;
    }

    public static function apply_match_result(int $player_id, int $new_elo, bool $won): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                'UPDATE ' . TP_Database::table('ranking_players') . '
                 SET elo_rating = %d,
                     matches_played = matches_played + 1,
                     wins = wins + %d,
                     losses = losses + %d
                 WHERE id = %d',
                $new_elo,
                $won ? 1 : 0,
                $won ? 0 : 1,
                $player_id
            )
        );
    }

    public static function set_elo(int $player_id, int $elo): void
    {
        global $wpdb;

        $wpdb->update(
            TP_Database::table('ranking_players'),
            ['elo_rating' => $elo],
            ['id' => $player_id],
            ['%d'],
            ['%d']
        );
    }

    /** @return list<array<string, mixed>> */
    public static function leaderboard(int $limit = 50): array
    {
        global $wpdb;

        $limit = max(1, min(500, $limit));
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . TP_Database::table('ranking_players') . '
                 WHERE matches_played > 0
                 ORDER BY elo_rating DESC, wins DESC, display_name ASC
                 LIMIT %d',
                $limit
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }
}
