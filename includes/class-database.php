<?php
/**
 * Helper nomi tabelle.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Database
{
    public static function table(string $suffix): string
    {
        global $wpdb;

        return $wpdb->prefix . 'tp_' . $suffix;
    }

    /** @return list<string> */
    public static function all_tables(): array
    {
        return [
            'tournaments',
            'tournament_settings',
            'tournament_organizers',
            'teams',
            'groups',
            'group_teams',
            'courts',
            'matches',
            'match_sets',
            'standings',
            'ranking_players',
            'elo_history',
            'player_notifications',
        ];
    }
}
