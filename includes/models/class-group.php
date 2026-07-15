<?php
/**
 * Model girone.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Group
{
    /** @return list<array<string, mixed>> */
    public static function for_tournament(int $tournament_id): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . TP_Database::table('groups') . ' WHERE tournament_id = %d ORDER BY sort_order ASC, name ASC',
                $tournament_id
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    /** @return list<array<string, mixed>> */
    public static function teams_in_group(int $group_id): array
    {
        global $wpdb;

        $gt = TP_Database::table('group_teams');
        $t  = TP_Database::table('teams');

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.* FROM {$t} t
                 INNER JOIN {$gt} gt ON gt.team_id = t.id
                 WHERE gt.group_id = %d
                 ORDER BY t.team_name ASC",
                $group_id
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    public static function create(int $tournament_id, string $name, int $sort_order = 0): int
    {
        global $wpdb;

        $wpdb->insert(
            TP_Database::table('groups'),
            [
                'tournament_id' => $tournament_id,
                'name'          => $name,
                'sort_order'    => $sort_order,
            ],
            ['%d', '%s', '%d']
        );

        return (int) $wpdb->insert_id;
    }

    public static function assign_team(int $group_id, int $team_id): void
    {
        global $wpdb;

        $wpdb->replace(
            TP_Database::table('group_teams'),
            ['group_id' => $group_id, 'team_id' => $team_id],
            ['%d', '%d']
        );
    }

    public static function clear_for_tournament(int $tournament_id): void
    {
        global $wpdb;

        $groups = self::for_tournament($tournament_id);
        foreach ($groups as $g) {
            $wpdb->delete(TP_Database::table('group_teams'), ['group_id' => (int) $g['id']], ['%d']);
        }
        $wpdb->delete(TP_Database::table('groups'), ['tournament_id' => $tournament_id], ['%d']);
    }
}
