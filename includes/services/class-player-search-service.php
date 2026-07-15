<?php
/**
 * Ricerca giocatori registrati (utenti WP + ranking).
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Player_Search_Service
{
    /** @return list<array{id: string, name: string, subtitle: string}> */
    public static function search(string $query, int $limit = 12): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $limit   = max(1, min(25, $limit));
        $results = [];
        $seen    = [];

        foreach (self::users_matching($query, $limit * 2) as $user) {
            if (! $user instanceof WP_User) {
                continue;
            }
            $name = self::display_name_for_user($user);
            if ($name === '') {
                continue;
            }
            $key = tp_normalize_player_name_key($name);
            if (isset($seen[$key])) {
                continue;
            }
            if (! self::name_matches_query($name, $query)) {
                continue;
            }
            $seen[$key] = true;
            $results[]  = [
                'id'       => 'user-' . (int) $user->ID,
                'name'     => $name,
                'subtitle' => self::subtitle_for_user($user),
            ];
            if (count($results) >= $limit) {
                return $results;
            }
        }

        global $wpdb;

        $like = '%' . $wpdb->esc_like($query) . '%';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id, display_name FROM ' . TP_Database::table('ranking_players') . '
                 WHERE display_name LIKE %s
                 ORDER BY matches_played DESC, elo_rating DESC, display_name ASC
                 LIMIT %d',
                $like,
                $limit * 2
            ),
            ARRAY_A
        );

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $name = trim((string) ($row['display_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $key = tp_normalize_player_name_key($name);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $results[]  = [
                    'id'       => 'ranking-' . (int) $row['id'],
                    'name'     => $name,
                    'subtitle' => __('Dal ranking', 'tornei-padel'),
                ];
                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /** @return list<string> */
    private static function searchable_roles(): array
    {
        $roles = apply_filters('tp_player_search_roles', [
            'tp_player',
            'tp_organizer',
            'subscriber',
            'administrator',
        ]);

        return array_values(array_filter(array_map('strval', (array) $roles)));
    }

    /** @return list<WP_User> */
    private static function users_matching(string $query, int $limit): array
    {
        $roles = self::searchable_roles();
        if ($roles === []) {
            return [];
        }

        $found = [];

        $by_search = new WP_User_Query([
            'role__in'         => $roles,
            'search'           => '*' . $query . '*',
            'search_columns'   => ['display_name', 'user_login', 'user_nicename', 'user_email'],
            'number'           => $limit,
            'orderby'          => 'display_name',
            'order'            => 'ASC',
            'suppress_filters' => false,
        ]);
        foreach ($by_search->get_results() as $user) {
            if ($user instanceof WP_User) {
                $found[(int) $user->ID] = $user;
            }
        }

        $by_meta = new WP_User_Query([
            'role__in'         => $roles,
            'number'           => $limit,
            'orderby'          => 'display_name',
            'order'            => 'ASC',
            'meta_query'       => [
                'relation' => 'OR',
                [
                    'key'     => 'first_name',
                    'value'   => $query,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => 'last_name',
                    'value'   => $query,
                    'compare' => 'LIKE',
                ],
            ],
            'suppress_filters' => false,
        ]);
        foreach ($by_meta->get_results() as $user) {
            if ($user instanceof WP_User) {
                $found[(int) $user->ID] = $user;
            }
        }

        return array_values($found);
    }

    private static function display_name_for_user(WP_User $user): string
    {
        $first = trim((string) get_user_meta($user->ID, 'first_name', true));
        $last  = trim((string) get_user_meta($user->ID, 'last_name', true));
        $full  = trim($first . ' ' . $last);
        if ($full !== '') {
            return $full;
        }

        $display = trim((string) $user->display_name);
        if ($display !== '' && $display !== $user->user_login) {
            return $display;
        }

        return $display !== '' ? $display : (string) $user->user_login;
    }

    private static function subtitle_for_user(WP_User $user): string
    {
        $roles = (array) $user->roles;

        if (in_array('administrator', $roles, true)) {
            return __('Amministratore', 'tornei-padel');
        }
        if (in_array('tp_organizer', $roles, true)) {
            return __('Organizzatore', 'tornei-padel');
        }
        if (in_array('tp_player', $roles, true)) {
            return __('Giocatore registrato', 'tornei-padel');
        }

        return __('Utente registrato', 'tornei-padel');
    }

    private static function name_matches_query(string $name, string $query): bool
    {
        return mb_stripos($name, $query) !== false;
    }
}
