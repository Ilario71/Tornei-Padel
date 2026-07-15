<?php
/**
 * Model squadra/coppia.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Team
{
    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . TP_Database::table('teams') . ' WHERE id = %d', $id),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public static function for_tournament(int $tournament_id): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . TP_Database::table('teams') . ' WHERE tournament_id = %d ORDER BY team_name ASC',
                $tournament_id
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    public static function count_for_tournament(int $tournament_id): int
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare('SELECT COUNT(*) FROM ' . TP_Database::table('teams') . ' WHERE tournament_id = %d', $tournament_id)
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert(
            TP_Database::table('teams'),
            [
                'tournament_id'  => (int) $data['tournament_id'],
                'team_name'      => $data['team_name'],
                'team_type'      => $data['team_type'] ?? 'double',
                'player1_name'   => $data['player1_name'],
                'player2_name'   => $data['player2_name'],
                'phone'          => $data['phone'] ?? null,
                'email'          => $data['email'] ?? null,
                'notes'          => $data['notes'] ?? null,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        $id = (int) $wpdb->insert_id;

        do_action(
            'tp_team_registered',
            (int) $data['tournament_id'],
            [
                'id'            => $id,
                'tournament_id' => (int) $data['tournament_id'],
                'team_name'     => (string) $data['team_name'],
                'player1_name'  => (string) $data['player1_name'],
                'player2_name'  => (string) $data['player2_name'],
            ]
        );

        return $id;
    }

    public static function delete(int $id): bool
    {
        global $wpdb;

        return $wpdb->delete(TP_Database::table('teams'), ['id' => $id], ['%d']) !== false;
    }
}
