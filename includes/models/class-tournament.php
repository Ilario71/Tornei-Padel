<?php
/**
 * Model torneo.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Tournament
{
    public static function slugify(string $name): string
    {
        $slug = sanitize_title($name);

        return $slug !== '' ? $slug : 'torneo';
    }

    public static function unique_slug(string $base, ?int $except_id = null): string
    {
        global $wpdb;

        $table = TP_Database::table('tournaments');
        $slug  = self::slugify($base);
        $try   = $slug;
        $i     = 1;

        while (true) {
            if ($except_id !== null) {
                $found = $wpdb->get_var(
                    $wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s AND id != %d", $try, $except_id)
                );
            } else {
                $found = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s", $try));
            }

            if ($found === null) {
                return $try;
            }

            $try = $slug . '-' . $i++;
        }
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . TP_Database::table('tournaments') . ' WHERE id = %d', $id),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public static function find_by_slug(string $slug): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . TP_Database::table('tournaments') . ' WHERE slug = %s', $slug),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public static function all(int $limit = 100): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . TP_Database::table('tournaments') . ' ORDER BY start_date DESC LIMIT %d',
                $limit
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    /** @return list<array<string, mixed>> */
    public static function live_enabled(): array
    {
        global $wpdb;

        $t = TP_Database::table('tournaments');
        $s = TP_Database::table('tournament_settings');

        $rows = $wpdb->get_results(
            "SELECT t.* FROM {$t} t
             INNER JOIN {$s} ts ON ts.tournament_id = t.id
             WHERE ts.live_enabled = 1
             ORDER BY t.start_date DESC",
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    public static function poster_url(?array $tournament, string $size = 'medium_large'): ?string
    {
        if ($tournament === null) {
            return null;
        }

        $id = (int) ($tournament['poster_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        $url = wp_get_attachment_image_url($id, $size);

        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert(
            TP_Database::table('tournaments'),
            [
                'name'                      => $data['name'],
                'slug'                      => $data['slug'],
                'description'               => $data['description'] ?? null,
                'poster_id'                 => ! empty($data['poster_id']) ? (int) $data['poster_id'] : null,
                'start_date'                => $data['start_date'],
                'end_date'                  => $data['end_date'],
                'num_teams'                 => (int) ($data['num_teams'] ?? 8),
                'num_groups'                => (int) ($data['num_groups'] ?? 2),
                'formula'                   => $data['formula'] ?? 'groups_knockout',
                'qualification_mode'        => $data['qualification_mode'] ?? 'gold_only',
                'qualification_count'       => (int) ($data['qualification_count'] ?? 2),
                'qualification_silver_from' => $data['qualification_silver_from'] ?? 'second_each_group',
                'sets_to_win'               => (int) ($data['sets_to_win'] ?? 2),
                'games_per_set'             => (int) ($data['games_per_set'] ?? 6),
                'match_duration'            => (int) ($data['match_duration'] ?? 60),
                'break_duration'            => (int) ($data['break_duration'] ?? 15),
                'daily_start_time'          => $data['daily_start_time'] ?? '09:00:00',
                'daily_end_time'            => $data['daily_end_time'] ?? '22:00:00',
                'created_by'                => $data['created_by'] ?? get_current_user_id(),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%d']
        );

        $id = (int) $wpdb->insert_id;

        $wpdb->insert(
            TP_Database::table('tournament_settings'),
            [
                'tournament_id'  => $id,
                'live_enabled'   => 1,
                'silver_enabled' => ($data['qualification_mode'] ?? '') === 'gold_silver' ? 1 : 0,
            ],
            ['%d', '%d', '%d']
        );

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        global $wpdb;

        $fields = [];
        $formats = [];

        $map = [
            'name' => '%s', 'slug' => '%s', 'description' => '%s', 'poster_id' => '%d',
            'start_date' => '%s', 'end_date' => '%s',
            'num_teams' => '%d', 'num_groups' => '%d', 'formula' => '%s',
            'qualification_mode' => '%s', 'qualification_count' => '%d',
            'qualification_silver_from' => '%s', 'sets_to_win' => '%d',
            'games_per_set' => '%d', 'match_duration' => '%d', 'break_duration' => '%d',
            'daily_start_time' => '%s', 'daily_end_time' => '%s',
        ];

        foreach ($map as $key => $fmt) {
            if (array_key_exists($key, $data)) {
                if ($key === 'poster_id') {
                    $fields[$key] = ! empty($data['poster_id']) ? (int) $data['poster_id'] : null;
                } else {
                    $fields[$key] = $data[$key];
                }
                $formats[] = $fmt;
            }
        }

        if ($fields === []) {
            return false;
        }

        return $wpdb->update(
            TP_Database::table('tournaments'),
            $fields,
            ['id' => $id],
            $formats,
            ['%d']
        ) !== false;
    }

    public static function delete(int $id): bool
    {
        global $wpdb;

        if (self::find($id) === null) {
            return false;
        }

        $t = static fn (string $s): string => TP_Database::table($s);

        // Storico ELO collegato alle partite del torneo (ripristina punteggi prima della cancellazione).
        $match_ids = $wpdb->get_col(
            $wpdb->prepare("SELECT id FROM {$t('matches')} WHERE tournament_id = %d", $id)
        );
        if (is_array($match_ids) && $match_ids !== []) {
            foreach ($match_ids as $match_id) {
                TP_Elo_Rating_Service::revert_if_processed((int) $match_id);
            }
            $placeholders = implode(',', array_fill(0, count($match_ids), '%d'));
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$t('match_sets')} WHERE match_id IN ({$placeholders})",
                    ...array_map('intval', $match_ids)
                )
            );
        }

        $wpdb->delete($t('matches'), ['tournament_id' => $id], ['%d']);
        $wpdb->delete($t('standings'), ['tournament_id' => $id], ['%d']);

        $group_ids = $wpdb->get_col(
            $wpdb->prepare("SELECT id FROM {$t('groups')} WHERE tournament_id = %d", $id)
        );
        if (is_array($group_ids)) {
            foreach ($group_ids as $gid) {
                $wpdb->delete($t('group_teams'), ['group_id' => (int) $gid], ['%d']);
            }
        }

        $wpdb->delete($t('groups'), ['tournament_id' => $id], ['%d']);
        $wpdb->delete($t('courts'), ['tournament_id' => $id], ['%d']);
        $wpdb->delete($t('teams'), ['tournament_id' => $id], ['%d']);
        $wpdb->delete($t('tournament_settings'), ['tournament_id' => $id], ['%d']);
        $wpdb->delete($t('tournament_organizers'), ['tournament_id' => $id], ['%d']);

        return $wpdb->delete($t('tournaments'), ['id' => $id], ['%d']) !== false;
    }

    /** @return array<string, mixed>|null */
    public static function settings(int $tournament_id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . TP_Database::table('tournament_settings') . ' WHERE tournament_id = %d', $tournament_id),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /** @return list<array{id: string, label: string, description: string}> */
    public static function formula_options(): array
    {
        return [
            [
                'id'          => 'groups_knockout',
                'label'       => __('Torneo a gironi', 'tornei-padel'),
                'description' => __('Gironi all\'italiana con tabellone finale Gold/Silver.', 'tornei-padel'),
            ],
            [
                'id'          => 'simple_match',
                'label'       => __('Match semplici', 'tornei-padel'),
                'description' => __('Girone unico tra coppie, senza tabelloni.', 'tornei-padel'),
            ],
            [
                'id'          => 'campionato',
                'label'       => __('Campionato', 'tornei-padel'),
                'description' => __('Andata e ritorno, solo classifica.', 'tornei-padel'),
            ],
            [
                'id'          => TP_Champions_Padel_Service::FORMULA,
                'label'       => __("Champion's Padel", 'tornei-padel'),
                'description' => __('32 squadre, 8 gironi da 4, ottavi stile Champions League.', 'tornei-padel'),
            ],
        ];
    }
}
