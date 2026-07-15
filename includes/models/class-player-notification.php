<?php
/**
 * Notifiche giocatore (area utente).
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Player_Notification
{
    public const TYPE_TEAM_REGISTERED = 'team_registered';
    public const TYPE_MATCH_RESULT    = 'match_result';

    public static function create(int $user_id, string $type, string $title, string $message = '', ?string $link = null): void
    {
        if ($user_id < 1 || ! self::table_exists()) {
            return;
        }

        global $wpdb;

        $wpdb->insert(
            TP_Database::table('player_notifications'),
            [
                'user_id' => $user_id,
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'link'    => $link,
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }

    /** @return list<array<string, mixed>> */
    public static function for_user(int $user_id, int $limit = 20, bool $unread_only = false): array
    {
        if (! self::table_exists()) {
            return [];
        }

        global $wpdb;

        $table = TP_Database::table('player_notifications');
        $sql   = "SELECT * FROM {$table} WHERE user_id = %d";
        if ($unread_only) {
            $sql .= ' AND read_at IS NULL';
        }
        $sql .= ' ORDER BY id DESC LIMIT %d';

        $rows = $wpdb->get_results(
            $wpdb->prepare($sql, $user_id, max(1, min(50, $limit))),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    public static function unread_count(int $user_id): int
    {
        if (! self::table_exists()) {
            return 0;
        }

        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . TP_Database::table('player_notifications') . ' WHERE user_id = %d AND read_at IS NULL',
                $user_id
            )
        );
    }

    public static function mark_read(int $notification_id, int $user_id): void
    {
        if (! self::table_exists()) {
            return;
        }

        global $wpdb;

        $wpdb->update(
            TP_Database::table('player_notifications'),
            ['read_at' => current_time('mysql')],
            ['id' => $notification_id, 'user_id' => $user_id],
            ['%s'],
            ['%d', '%d']
        );
    }

    public static function mark_all_read(int $user_id): void
    {
        if (! self::table_exists()) {
            return;
        }

        global $wpdb;

        $table = TP_Database::table('player_notifications');
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET read_at = %s WHERE user_id = %d AND read_at IS NULL",
                current_time('mysql'),
                $user_id
            )
        );
    }

    public static function notify_team_registered(int $tournament_id, array $team): void
    {
        $tournament = TP_Tournament::find($tournament_id);
        if ($tournament === null) {
            return;
        }

        $t_name = (string) $tournament['name'];
        $slug   = (string) $tournament['slug'];
        $link   = home_url('/torneo-live/' . $slug);
        $team_label = (string) ($team['team_name'] ?? '');

        foreach (TP_Player_User_Resolver::user_ids_for_team_row($team) as $user_id) {
            self::create(
                $user_id,
                self::TYPE_TEAM_REGISTERED,
                sprintf(
                    /* translators: %s: tournament name */
                    __('Iscritto al torneo «%s»', 'tornei-padel'),
                    $t_name
                ),
                $team_label !== ''
                    ? sprintf(__('Squadra: %s', 'tornei-padel'), $team_label)
                    : __('La tua coppia è stata registrata.', 'tornei-padel'),
                $link
            );
        }
    }

    public static function notify_match_result(int $match_id): void
    {
        $match = TP_Match::find($match_id);
        if ($match === null || ($match['status'] ?? '') !== 'finished') {
            return;
        }

        $tournament = TP_Tournament::find((int) $match['tournament_id']);
        if ($tournament === null) {
            return;
        }

        $slug = (string) $tournament['slug'];
        $link = home_url('/torneo-live/' . $slug . '/partita/' . $match_id);
        $round = (string) ($match['round_name'] ?? '');
        $title = sprintf(
            /* translators: %s: tournament name */
            __('Risultato inserito — %s', 'tornei-padel'),
            (string) $tournament['name']
        );
        $message = $round !== '' ? $round : __('Una tua partita è stata aggiornata.', 'tornei-padel');

        $notified = [];
        foreach ([(int) ($match['team1_id'] ?? 0), (int) ($match['team2_id'] ?? 0)] as $team_id) {
            if ($team_id < 1) {
                continue;
            }
            $team = TP_Team::find($team_id);
            if ($team === null) {
                continue;
            }
            foreach (TP_Player_User_Resolver::user_ids_for_team_row($team) as $user_id) {
                if (isset($notified[$user_id])) {
                    continue;
                }
                $notified[$user_id] = true;
                self::create($user_id, self::TYPE_MATCH_RESULT, $title, $message, $link);
            }
        }
    }

    private static function table_exists(): bool
    {
        global $wpdb;

        $table = TP_Database::table('player_notifications');
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }
}
