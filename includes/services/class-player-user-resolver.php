<?php
/**
 * Risolve utente WordPress da nome giocatore torneo.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Player_User_Resolver
{
    public static function user_id_for_player_name(string $display_name): ?int
    {
        $display = trim($display_name);
        if ($display === '') {
            return null;
        }

        if (class_exists('TP_Database') && function_exists('tp_normalize_player_name_key')) {
            global $wpdb;

            $table = TP_Database::table('ranking_players');
            $key   = tp_normalize_player_name_key($display);
            $uid   = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT user_id FROM {$table} WHERE name_key = %s AND user_id IS NOT NULL LIMIT 1",
                    $key
                )
            );
            if ($uid !== null && (int) $uid > 0) {
                return (int) $uid;
            }
        }

        $parts = preg_split('/\s+/u', $display) ?: [];
        if (count($parts) >= 2) {
            $query = new WP_User_Query([
                'number'     => 1,
                'meta_query' => [
                    'relation' => 'AND',
                    ['key' => 'first_name', 'value' => $parts[0], 'compare' => '='],
                    ['key' => 'last_name', 'value' => implode(' ', array_slice($parts, 1)), 'compare' => '='],
                ],
            ]);
            $users = $query->get_results();
            if ($users !== [] && $users[0] instanceof WP_User) {
                return (int) $users[0]->ID;
            }
        }

        $by_display = get_users([
            'search'         => $display,
            'search_columns' => ['display_name'],
            'number'         => 5,
        ]);
        foreach ($by_display as $user) {
            if (! $user instanceof WP_User) {
                continue;
            }
            if (strcasecmp(trim($user->display_name), $display) === 0) {
                return (int) $user->ID;
            }
        }

        return null;
    }

    /** @return list<int> */
    public static function user_ids_for_team_row(array $team): array
    {
        $ids = [];
        foreach (['player1_name', 'player2_name'] as $field) {
            $uid = self::user_id_for_player_name((string) ($team[$field] ?? ''));
            if ($uid !== null) {
                $ids[$uid] = $uid;
            }
        }

        return array_values($ids);
    }
}
