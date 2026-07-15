<?php
/**
 * Model partita.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Match
{
    public static function match_url(string $tournament_slug, int $match_id): string
    {
        return home_url('/torneo-live/' . $tournament_slug . '/partita/' . $match_id);
    }

    /**
     * Immagine vista aerea del campo per la pagina live partita (distinta dall'hero homepage).
     */
    public static function court_image_url(): string
    {
        $relative = 'assets/images/campo-padel.png';
        $theme_path = get_theme_file_path($relative);
        $theme_uri  = get_theme_file_uri($relative);

        if (is_string($theme_path) && $theme_path !== '' && file_exists($theme_path)
            && is_string($theme_uri) && $theme_uri !== '') {
            return $theme_uri;
        }

        $plugin_file = TP_PLUGIN_DIR . 'public/images/campo-padel.png';
        if (file_exists($plugin_file)) {
            return TP_PLUGIN_URL . 'public/images/campo-padel.png';
        }

        return is_string($theme_uri) && $theme_uri !== ''
            ? $theme_uri
            : TP_PLUGIN_URL . 'public/images/campo-padel.png';
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        global $wpdb;

        $m = TP_Database::table('matches');
        $t = TP_Database::table('teams');
        $c = TP_Database::table('courts');

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT m.*,
                        t1.team_name AS team1_name, t1.player1_name AS t1p1, t1.player2_name AS t1p2,
                        t2.team_name AS team2_name, t2.player1_name AS t2p1, t2.player2_name AS t2p2,
                        c.name AS court_name
                 FROM {$m} m
                 LEFT JOIN {$t} t1 ON t1.id = m.team1_id
                 LEFT JOIN {$t} t2 ON t2.id = m.team2_id
                 LEFT JOIN {$c} c ON c.id = m.court_id
                 WHERE m.id = %d",
                $id
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public static function for_tournament(int $tournament_id, ?string $phase = null): array
    {
        global $wpdb;

        $m = TP_Database::table('matches');
        $t = TP_Database::table('teams');
        $c = TP_Database::table('courts');

        $sql = "SELECT m.*,
                       t1.team_name AS team1_name,
                       t2.team_name AS team2_name,
                       tw.team_name AS winner_name,
                       c.name AS court_name
                FROM {$m} m
                LEFT JOIN {$t} t1 ON t1.id = m.team1_id
                LEFT JOIN {$t} t2 ON t2.id = m.team2_id
                LEFT JOIN {$t} tw ON tw.id = m.winner_team_id
                LEFT JOIN {$c} c ON c.id = m.court_id
                WHERE m.tournament_id = %d";
        $args = [$tournament_id];

        if ($phase !== null) {
            $sql   .= ' AND m.phase = %s';
            $args[] = $phase;
        }

        $sql .= ' ORDER BY m.phase ASC, m.bracket_type ASC, m.round_index ASC, m.bracket_slot ASC, m.match_date ASC, m.start_time ASC, m.id ASC';

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    /** @return list<array<string, mixed>> */
    public static function sets_for_match(int $match_id): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . TP_Database::table('match_sets') . ' WHERE match_id = %d ORDER BY set_number ASC',
                $match_id
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert(
            TP_Database::table('matches'),
            [
                'tournament_id' => (int) $data['tournament_id'],
                'phase'         => $data['phase'] ?? 'group',
                'bracket_type'  => $data['bracket_type'] ?? 'none',
                'group_id'      => $data['group_id'] ?? null,
                'team1_id'      => $data['team1_id'] ?? null,
                'team2_id'      => $data['team2_id'] ?? null,
                'court_id'      => $data['court_id'] ?? null,
                'match_date'    => $data['match_date'] ?? null,
                'start_time'    => $data['start_time'] ?? null,
                'end_time'      => $data['end_time'] ?? null,
                'round_name'    => $data['round_name'] ?? null,
                'round_index'   => (int) ($data['round_index'] ?? 0),
                'bracket_slot'  => $data['bracket_slot'] ?? null,
                'status'        => $data['status'] ?? 'scheduled',
            ],
            ['%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * @param list<array{set_number:int, team1_score:int, team2_score:int, is_super_tiebreak?:bool}> $sets
     */
    public static function replace_sets(int $match_id, array $sets): void
    {
        global $wpdb;

        $wpdb->delete(TP_Database::table('match_sets'), ['match_id' => $match_id], ['%d']);

        foreach ($sets as $set) {
            $wpdb->insert(
                TP_Database::table('match_sets'),
                [
                    'match_id'           => $match_id,
                    'set_number'         => (int) $set['set_number'],
                    'team1_score'        => (int) $set['team1_score'],
                    'team2_score'        => (int) $set['team2_score'],
                    'is_super_tiebreak'  => ! empty($set['is_super_tiebreak']) ? 1 : 0,
                ],
                ['%d', '%d', '%d', '%d', '%d']
            );
        }
    }

    /**
     * Salva punteggi parziali: status live, nessun vincitore, classifica invariata.
     *
     * @param list<array{set_number:int, team1_score:int, team2_score:int}> $sets
     */
    public static function save_live_partial(int $match_id, array $sets): void
    {
        global $wpdb;

        self::replace_sets($match_id, $sets);

        $table = TP_Database::table('matches');
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status = 'live', winner_team_id = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = %d",
                $match_id
            )
        );

        TP_Elo_Rating_Service::revert_if_processed($match_id);
    }

    /**
     * @param list<array{set_number:int, team1_score:int, team2_score:int}> $sets
     * @return string|null Avviso se il ranking ELO non è stato aggiornato
     */
    public static function save_result(int $match_id, int $winner_team_id, array $sets): ?string
    {
        global $wpdb;

        self::replace_sets($match_id, $sets);

        $wpdb->update(
            TP_Database::table('matches'),
            [
                'winner_team_id' => $winner_team_id,
                'status'         => 'finished',
            ],
            ['id' => $match_id],
            ['%d', '%s'],
            ['%d']
        );

        $warn = self::sync_ranking($match_id);
        do_action('tp_match_finished', $match_id);

        return $warn;
    }

    public static function set_winner(int $match_id, int $winner_team_id, string $status = 'finished'): void
    {
        global $wpdb;

        $wpdb->update(
            TP_Database::table('matches'),
            [
                'winner_team_id' => $winner_team_id,
                'status'         => $status,
            ],
            ['id' => $match_id],
            ['%d', '%s'],
            ['%d']
        );

        self::sync_ranking($match_id);
    }

    /**
     * Aggiorna ranking ELO dopo commit del risultato (non blocca il salvataggio).
     */
    public static function sync_ranking(int $match_id): ?string
    {
        try {
            if (! TP_Elo_Rating_Service::process_match($match_id)) {
                return __(
                    'Risultato salvato, ma il ranking non è stato aggiornato: su ogni coppia servono Giocatore 1 e Giocatore 2 con nome reale (non «Da indicare» o «Coppia 1»). Modifica le squadre nel torneo e salva di nuovo il risultato.',
                    'tornei-padel'
                );
            }
        } catch (Throwable $e) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('tp elo process failed match ' . $match_id . ': ' . $e->getMessage());

            return __(
                'Risultato salvato, ma il ranking non è stato aggiornato per un errore tecnico.',
                'tornei-padel'
            );
        }

        return null;
    }

    public static function delete_knockout(int $tournament_id): void
    {
        global $wpdb;

        $m_table  = TP_Database::table('matches');
        $ms_table = TP_Database::table('match_sets');

        $ids = $wpdb->get_col(
            $wpdb->prepare("SELECT id FROM {$m_table} WHERE tournament_id = %d AND phase = 'knockout'", $tournament_id)
        );

        if (is_array($ids)) {
            foreach ($ids as $mid) {
                $wpdb->delete($ms_table, ['match_id' => (int) $mid], ['%d']);
            }
        }

        $wpdb->delete($m_table, ['tournament_id' => $tournament_id, 'phase' => 'knockout'], ['%d', '%s']);
    }

    public static function count_by_phase(int $tournament_id, string $phase): int
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . TP_Database::table('matches') . ' WHERE tournament_id = %d AND phase = %s',
                $tournament_id,
                $phase
            )
        );
    }

    public static function set_court(int $match_id, ?int $court_id): void
    {
        global $wpdb;

        $wpdb->update(
            TP_Database::table('matches'),
            ['court_id' => $court_id !== null && $court_id > 0 ? $court_id : null],
            ['id' => $match_id],
            ['%d'],
            ['%d']
        );
    }

    public static function set_status(int $match_id, string $status): void
    {
        global $wpdb;

        $wpdb->update(
            TP_Database::table('matches'),
            ['status' => $status],
            ['id' => $match_id],
            ['%s'],
            ['%d']
        );
    }

    public static function delete_for_tournament(int $tournament_id): void
    {
        global $wpdb;

        $matches = $wpdb->get_col(
            $wpdb->prepare('SELECT id FROM ' . TP_Database::table('matches') . ' WHERE tournament_id = %d', $tournament_id)
        );

        if (is_array($matches)) {
            foreach ($matches as $mid) {
                $wpdb->delete(TP_Database::table('match_sets'), ['match_id' => (int) $mid], ['%d']);
            }
        }

        $wpdb->delete(TP_Database::table('matches'), ['tournament_id' => $tournament_id], ['%d']);
    }
}
