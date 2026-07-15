<?php
/**
 * Bacheca WordPress personalizzata per Tornei Padel.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Dashboard
{
    public static function init(): void
    {
        add_action('admin_init', [self::class, 'remove_welcome_panel']);
        add_action('wp_dashboard_setup', [self::class, 'setup'], 999);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function remove_welcome_panel(): void
    {
        if (! self::user_sees_dashboard()) {
            return;
        }

        remove_action('welcome_panel', 'wp_welcome_panel');
    }

    public static function setup(): void
    {
        if (! self::user_sees_dashboard()) {
            return;
        }

        global $wp_meta_boxes;

        $wp_meta_boxes['dashboard'] = [
            'normal' => ['core' => []],
            'side'   => ['core' => []],
        ];

        wp_add_dashboard_widget(
            'tp_dashboard_overview',
            __('Panoramica tornei', 'tornei-padel'),
            [self::class, 'render_overview']
        );

        wp_add_dashboard_widget(
            'tp_dashboard_tournaments',
            __('Tornei in programma', 'tornei-padel'),
            [self::class, 'render_tournaments']
        );

        wp_add_dashboard_widget(
            'tp_dashboard_pending_matches',
            __('Partite da completare', 'tornei-padel'),
            [self::class, 'render_pending_matches']
        );

        wp_add_dashboard_widget(
            'tp_dashboard_quick_actions',
            __('Azioni rapide', 'tornei-padel'),
            [self::class, 'render_quick_actions'],
            null,
            null,
            'side',
            'core'
        );

        wp_add_dashboard_widget(
            'tp_dashboard_ranking',
            __('Top ranking', 'tornei-padel'),
            [self::class, 'render_ranking'],
            null,
            null,
            'side',
            'core'
        );

        wp_add_dashboard_widget(
            'tp_dashboard_live',
            __('Tornei LIVE', 'tornei-padel'),
            [self::class, 'render_live_tournaments'],
            null,
            null,
            'side',
            'core'
        );
    }

    public static function enqueue_assets(string $hook): void
    {
        if ($hook !== 'index.php' || ! self::user_sees_dashboard()) {
            return;
        }

        wp_enqueue_style(
            'tp-admin',
            TP_PLUGIN_URL . 'admin/css/admin.css',
            [],
            TP_VERSION
        );
    }

    public static function user_sees_dashboard(): bool
    {
        return TP_Roles::can_edit() || current_user_can('manage_options');
    }

    public static function render_overview(): void
    {
        self::render_partial('overview', [
            'stats' => self::stats(),
        ]);
    }

    public static function render_tournaments(): void
    {
        self::render_partial('tournaments', [
            'tournaments' => self::upcoming_tournaments(6),
        ]);
    }

    public static function render_pending_matches(): void
    {
        self::render_partial('pending-matches', [
            'matches' => self::pending_matches(8),
        ]);
    }

    public static function render_quick_actions(): void
    {
        self::render_partial('quick-actions');
    }

    public static function render_ranking(): void
    {
        self::render_partial('ranking', [
            'players' => TP_Ranking_Player::leaderboard(5),
        ]);
    }

    public static function render_live_tournaments(): void
    {
        self::render_partial('live-tournaments', [
            'tournaments' => TP_Tournament::live_enabled(),
        ]);
    }

    /**
     * @param array<string, mixed> $vars
     */
    private static function render_partial(string $name, array $vars = []): void
    {
        $path = TP_PLUGIN_DIR . 'includes/admin/views/dashboard/' . $name . '.php';

        if (! is_readable($path)) {
            return;
        }

        extract($vars, EXTR_SKIP);
        include $path;
    }

    /** @return array{tournaments: int, teams: int, players: int, pending_matches: int, live_matches: int} */
    public static function stats(): array
    {
        global $wpdb;

        $tournaments = TP_Database::table('tournaments');
        $teams       = TP_Database::table('teams');
        $players     = TP_Database::table('ranking_players');
        $matches     = TP_Database::table('matches');

        return [
            'tournaments'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tournaments}"),
            'teams'            => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$teams}"),
            'players'          => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$players}"),
            'pending_matches'  => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$matches}
                 WHERE team1_id IS NOT NULL
                   AND team2_id IS NOT NULL
                   AND status IN ('scheduled', 'live')"
            ),
            'live_matches'     => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$matches} WHERE status = 'live'"
            ),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function upcoming_tournaments(int $limit = 6): array
    {
        global $wpdb;

        $limit = max(1, min(20, $limit));
        $table = TP_Database::table('tournaments');
        $today = gmdate('Y-m-d');

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE end_date >= %s
                 ORDER BY start_date ASC
                 LIMIT %d",
                $today,
                $limit
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    /** @return list<array<string, mixed>> */
    public static function pending_matches(int $limit = 8): array
    {
        global $wpdb;

        $limit = max(1, min(20, $limit));
        $m     = TP_Database::table('matches');
        $t     = TP_Database::table('tournaments');
        $teams = TP_Database::table('teams');

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.id, m.tournament_id, m.match_date, m.start_time, m.status, m.phase,
                        tor.name AS tournament_name, tor.slug AS tournament_slug,
                        t1.team_name AS team1_name, t2.team_name AS team2_name
                 FROM {$m} m
                 INNER JOIN {$t} tor ON tor.id = m.tournament_id
                 LEFT JOIN {$teams} t1 ON t1.id = m.team1_id
                 LEFT JOIN {$teams} t2 ON t2.id = m.team2_id
                 WHERE m.team1_id IS NOT NULL
                   AND m.team2_id IS NOT NULL
                   AND m.status IN ('scheduled', 'live')
                 ORDER BY m.match_date ASC, m.start_time ASC, m.id ASC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    public static function formula_label(string $formula): string
    {
        foreach (TP_Tournament::formula_options() as $option) {
            if (($option['id'] ?? '') === $formula) {
                return (string) ($option['label'] ?? $formula);
            }
        }

        return $formula;
    }

    public static function phase_label(string $phase): string
    {
        return match ($phase) {
            'group'    => __('Gironi', 'tornei-padel'),
            'knockout' => __('Eliminazione', 'tornei-padel'),
            default    => ucfirst($phase),
        };
    }

    public static function status_label(string $status): string
    {
        return match ($status) {
            'live'      => __('In corso', 'tornei-padel'),
            'scheduled' => __('Da giocare', 'tornei-padel'),
            'finished'  => __('Conclusa', 'tornei-padel'),
            default     => ucfirst($status),
        };
    }
}
