<?php
/**
 * Pagine admin tornei.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Tournaments_Page
{
    public static function register_handlers(): void
    {
        add_action('admin_post_tp_save_tournament', [self::class, 'handle_save']);
        add_action('admin_post_tp_delete_tournament', [self::class, 'handle_delete']);
        add_action('admin_post_tp_save_team', [self::class, 'handle_save_team']);
        add_action('admin_post_tp_delete_team', [self::class, 'handle_delete_team']);
        add_action('admin_post_tp_draw_random', [self::class, 'handle_draw_random']);
        add_action('admin_post_tp_generate_matches', [self::class, 'handle_generate_matches']);
        add_action('admin_post_tp_recalc_standings', [self::class, 'handle_recalc_standings']);
        add_action('admin_post_tp_rebuild_knockout', [self::class, 'handle_rebuild_knockout']);
        add_action('admin_post_tp_save_match_result', [self::class, 'handle_save_match_result']);
    }

    private static function ensure_ready(): void
    {
        TP_Activator::ensure_schema();
    }

    private static function redirect_edit(int $tournament_id, string $query = ''): never
    {
        $url = admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id);
        if ($query !== '') {
            $url .= '&' . ltrim($query, '&');
        }
        wp_safe_redirect($url);
        exit;
    }

    /**
     * @param callable(): void $action
     */
    private static function run_tournament_action(int $tournament_id, callable $action, string $success_query): void
    {
        self::ensure_ready();

        try {
            $action();
            self::redirect_edit($tournament_id, $success_query);
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('[Tornei Padel] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            }

            if ($e instanceof RuntimeException) {
                $message = $e->getMessage();
            } elseif (defined('WP_DEBUG') && WP_DEBUG) {
                $message = $e->getMessage();
            } else {
                $message = __('Operazione non riuscita. Verifica i dati del torneo e riprova.', 'tornei-padel');
            }

            self::redirect_edit($tournament_id, 'tp_error=' . rawurlencode($message));
        }
    }

    public static function render_list(): void
    {
        if (! TP_Roles::can_edit()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        $tournaments = TP_Tournament::all();
        include TP_PLUGIN_DIR . 'includes/admin/views/tournaments-list.php';
    }

    public static function render_create(): void
    {
        if (! TP_Roles::can_manage()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        $tournament = null;
        $action     = 'create';
        include TP_PLUGIN_DIR . 'includes/admin/views/tournament-form.php';
    }

    public static function render_edit(): void
    {
        if (! TP_Roles::can_edit()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        self::ensure_ready();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $tournament = TP_Tournament::find($id);

        if ($tournament === null) {
            wp_die(esc_html__('Torneo non trovato.', 'tornei-padel'));
        }

        $teams            = TP_Team::for_tournament($id);
        $groups           = TP_Group::for_tournament($id);
        $group_matches    = TP_Match::for_tournament($id, 'group');
        $knockout_matches = TP_Match::for_tournament($id, 'knockout');
        $settings         = TP_Tournament::settings($id);
        $supports_knockout = TP_Knockout_Service::supports_brackets($tournament);
        $qualified_preview = $supports_knockout ? TP_Knockout_Service::preview_qualified($id) : ['gold' => [], 'silver' => []];
        $action           = 'edit';

        include TP_PLUGIN_DIR . 'includes/admin/views/tournament-edit.php';
    }

    public static function handle_save(): void
    {
        if (! TP_Roles::can_manage()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        check_admin_referer('tp_save_tournament');

        $id   = isset($_POST['tournament_id']) ? (int) $_POST['tournament_id'] : 0;
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));

        if ($name === '') {
            wp_safe_redirect(admin_url('admin.php?page=tp-tournament-new&error=1'));
            exit;
        }

        $num_courts = max(1, min(16, (int) ($_POST['num_courts'] ?? 2)));
        $formula    = sanitize_key($_POST['formula'] ?? 'groups_knockout');

        $data = [
            'name'                      => $name,
            'description'               => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'poster_id'                 => max(0, (int) ($_POST['poster_id'] ?? 0)),
            'start_date'                => sanitize_text_field(wp_unslash($_POST['start_date'] ?? '')),
            'end_date'                  => sanitize_text_field(wp_unslash($_POST['end_date'] ?? '')),
            'num_teams'                 => max(2, (int) ($_POST['num_teams'] ?? 8)),
            'num_groups'                => max(1, (int) ($_POST['num_groups'] ?? 2)),
            'formula'                   => $formula,
            'qualification_mode'        => sanitize_key($_POST['qualification_mode'] ?? 'gold_only'),
            'qualification_count'       => max(1, (int) ($_POST['qualification_count'] ?? 2)),
            'qualification_silver_from' => sanitize_key($_POST['qualification_silver_from'] ?? 'second_each_group'),
            'sets_to_win'               => max(1, (int) ($_POST['sets_to_win'] ?? 2)),
            'games_per_set'             => max(4, (int) ($_POST['games_per_set'] ?? 6)),
            'match_duration'            => max(15, (int) ($_POST['match_duration'] ?? 60)),
            'break_duration'            => max(0, (int) ($_POST['break_duration'] ?? 15)),
            'daily_start_time'          => sanitize_text_field(wp_unslash($_POST['daily_start_time'] ?? '09:00')),
            'daily_end_time'            => sanitize_text_field(wp_unslash($_POST['daily_end_time'] ?? '22:00')),
        ];

        if ($formula === TP_Champions_Padel_Service::FORMULA) {
            $phase = TP_Champions_Padel_Service::default_phase_choice();
            $mf    = TP_Champions_Padel_Service::default_match_format();
            $data['num_teams']                 = TP_Champions_Padel_Service::NUM_TEAMS;
            $data['num_groups']                = TP_Champions_Padel_Service::NUM_GROUPS;
            $data['qualification_mode']        = $phase['qualification_mode'];
            $data['qualification_count']       = $phase['qualification_count'];
            $data['qualification_silver_from'] = $phase['qualification_silver_from'];
            $data['sets_to_win']               = $mf['sets_to_win'];
            $data['games_per_set']             = $mf['games_per_set'];
            $data['match_duration']            = $mf['match_duration'];
            $data['break_duration']            = $mf['break_duration'];
        }

        if ($id > 0) {
            $existing = TP_Tournament::find($id);
            if ($existing === null) {
                wp_die(esc_html__('Torneo non trovato.', 'tornei-padel'));
            }

            $preserve = [
                'num_teams', 'num_groups', 'formula', 'qualification_mode',
                'qualification_count', 'qualification_silver_from', 'sets_to_win',
                'games_per_set', 'match_duration', 'break_duration',
                'daily_start_time', 'daily_end_time',
            ];
            foreach ($preserve as $key) {
                if (! isset($_POST[$key]) && isset($existing[$key])) {
                    $data[$key] = $existing[$key];
                }
            }

            $data['slug'] = TP_Tournament::unique_slug($name, $id);

            self::run_tournament_action($id, static function () use ($id, $data, $num_courts): void {
                TP_Tournament::update($id, $data);
                TP_Court::sync_count($id, $num_courts);
            }, 'saved=1');

            return;
        }

        self::ensure_ready();

        try {
            $data['slug'] = TP_Tournament::unique_slug($name);
            $redirect_id  = TP_Tournament::create($data);
            TP_Court::sync_count($redirect_id, $num_courts);
            self::create_staged_teams($redirect_id);
            if ($formula === TP_Champions_Padel_Service::FORMULA
                && TP_Team::count_for_tournament($redirect_id) === TP_Champions_Padel_Service::NUM_TEAMS
            ) {
                try {
                    TP_Champions_Padel_Service::bootstrap_group_phase($redirect_id);
                } catch (Throwable $e) {
                    // Sorteggio manuale dalla pagina torneo.
                }
            }
            wp_safe_redirect(admin_url('admin.php?page=tp-tournament-edit&id=' . $redirect_id . '&saved=1'));
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('[Tornei Padel] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            }
            wp_safe_redirect(admin_url('admin.php?page=tp-tournament-new&error=1'));
        }
        exit;
    }

    public static function handle_delete(): void
    {
        if (! TP_Roles::can_manage()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        $id = isset($_POST['tournament_id']) ? (int) $_POST['tournament_id'] : 0;
        check_admin_referer('tp_delete_tournament_' . $id);

        if ($id > 0 && TP_Tournament::find($id) !== null) {
            TP_Tournament::delete($id);
            wp_safe_redirect(admin_url('admin.php?page=tp-tournaments&deleted=1'));
            exit;
        }

        wp_safe_redirect(admin_url('admin.php?page=tp-tournaments&delete_error=1'));
        exit;
    }

    public static function handle_save_team(): void
    {
        if (! TP_Roles::can_edit()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        $tournament_id = (int) ($_POST['tournament_id'] ?? 0);
        check_admin_referer('tp_save_team_' . $tournament_id);

        $p1 = sanitize_text_field(wp_unslash($_POST['player1_name'] ?? ''));
        $p2 = sanitize_text_field(wp_unslash($_POST['player2_name'] ?? ''));
        $team_name = sanitize_text_field(wp_unslash($_POST['team_name'] ?? ''));

        if ($team_name === '') {
            $team_name = trim($p1 . ' / ' . $p2);
        }

        if ($p1 === '' || $p2 === '') {
            wp_safe_redirect(admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id . '&team_error=1'));
            exit;
        }

        $tournament = TP_Tournament::find($tournament_id);
        if ($tournament !== null) {
            $max = max(0, (int) ($tournament['num_teams'] ?? 0));
            if ($max > 0 && TP_Team::count_for_tournament($tournament_id) >= $max) {
                wp_safe_redirect(admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id . '&team_limit=1'));
                exit;
            }
        }

        TP_Team::create([
            'tournament_id' => $tournament_id,
            'team_name'     => $team_name,
            'player1_name'  => $p1,
            'player2_name'  => $p2,
            'phone'         => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
            'email'         => sanitize_email(wp_unslash($_POST['email'] ?? '')),
        ]);

        wp_safe_redirect(admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id . '&team_saved=1'));
        exit;
    }

    private static function create_staged_teams(int $tournament_id): void
    {
        if (empty($_POST['staged_teams']) || ! is_array($_POST['staged_teams'])) {
            return;
        }

        foreach ($_POST['staged_teams'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $p1 = sanitize_text_field(wp_unslash((string) ($row['player1_name'] ?? '')));
            $p2 = sanitize_text_field(wp_unslash((string) ($row['player2_name'] ?? '')));
            if ($p1 === '' || $p2 === '') {
                continue;
            }

            $team_name = sanitize_text_field(wp_unslash((string) ($row['team_name'] ?? '')));
            if ($team_name === '') {
                $team_name = trim($p1 . ' / ' . $p2);
            }

            TP_Team::create([
                'tournament_id' => $tournament_id,
                'team_name'     => $team_name,
                'player1_name'  => $p1,
                'player2_name'  => $p2,
            ]);
        }
    }

    public static function handle_delete_team(): void
    {
        if (! TP_Roles::can_edit()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        $tournament_id = (int) ($_POST['tournament_id'] ?? 0);
        $team_id       = (int) ($_POST['team_id'] ?? 0);
        check_admin_referer('tp_delete_team_' . $team_id);

        TP_Team::delete($team_id);

        wp_safe_redirect(admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id));
        exit;
    }

    public static function handle_draw_random(): void
    {
        if (! TP_Roles::can_edit()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        $tournament_id = (int) ($_POST['tournament_id'] ?? 0);
        check_admin_referer('tp_draw_random_' . $tournament_id);

        self::run_tournament_action(
            $tournament_id,
            static fn (): mixed => TP_Group_Draw_Service::draw_random($tournament_id),
            'drawn=1'
        );
    }

    public static function handle_generate_matches(): void
    {
        if (! TP_Roles::can_edit()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        $tournament_id = (int) ($_POST['tournament_id'] ?? 0);
        check_admin_referer('tp_generate_matches_' . $tournament_id);

        self::run_tournament_action(
            $tournament_id,
            static fn (): mixed => TP_Group_Draw_Service::generate_group_matches($tournament_id),
            'matches=1'
        );
    }

    public static function handle_recalc_standings(): void
    {
        if (! TP_Roles::can_edit()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        $tournament_id = (int) ($_POST['tournament_id'] ?? 0);
        check_admin_referer('tp_recalc_standings_' . $tournament_id);

        self::run_tournament_action(
            $tournament_id,
            static fn (): mixed => TP_Standings_Service::recalculate_tournament($tournament_id),
            'standings=1'
        );
    }

    public static function handle_rebuild_knockout(): void
    {
        if (! TP_Roles::can_edit()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        $tournament_id = (int) ($_POST['tournament_id'] ?? 0);
        check_admin_referer('tp_rebuild_knockout_' . $tournament_id);

        self::run_tournament_action(
            $tournament_id,
            static fn (): mixed => TP_Knockout_Service::rebuild_from_standings($tournament_id),
            'knockout_built=1'
        );
    }

    public static function handle_save_match_result(): void
    {
        if (! TP_Roles::can_enter_results()) {
            wp_die(esc_html__('Permessi insufficienti.', 'tornei-padel'));
        }

        $match_id      = (int) ($_POST['match_id'] ?? 0);
        $tournament_id = (int) ($_POST['tournament_id'] ?? 0);
        $save_type     = sanitize_key((string) ($_POST['save_type'] ?? 'auto'));
        check_admin_referer('tp_save_match_' . $match_id);

        $match = TP_Match::find($match_id);
        if ($match === null) {
            wp_die(esc_html__('Partita non trovata.', 'tornei-padel'));
        }

        $tournament = TP_Tournament::find($tournament_id);
        if ($tournament === null) {
            wp_die(esc_html__('Torneo non trovato.', 'tornei-padel'));
        }

        $sets        = TP_Match_Result_Service::parse_sets_from_post();
        $sets_to_win = max(1, (int) ($tournament['sets_to_win'] ?? 2));

        $validation_error = TP_Scoring_Rules_Service::validate_match_sets($tournament, $sets);
        if ($validation_error !== null && $save_type === 'final') {
            wp_safe_redirect(admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id . '&tp_error=' . rawurlencode($validation_error)));
            exit;
        }

        if (! TP_Match_Result_Service::sets_have_any_score($sets)) {
            wp_safe_redirect(admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id . '&result_error=empty'));
            exit;
        }

        $is_complete = TP_Match_Result_Service::is_match_complete($sets, $sets_to_win);
        $winner      = TP_Match_Result_Service::determine_winner($match, $sets);

        if ($save_type === 'final') {
            if (! $is_complete || $winner === null) {
                wp_safe_redirect(admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id . '&result_error=incomplete'));
                exit;
            }
            $elo_warn = TP_Match::save_result($match_id, $winner, $sets);

            if (($match['phase'] ?? '') === 'knockout') {
                TP_Knockout_Service::after_match_resolved($match_id);
            } elseif ((int) ($match['group_id'] ?? 0) > 0) {
                TP_Standings_Service::recalculate_group($tournament_id, (int) $match['group_id']);
            }

            $redirect = admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id . '&result_saved=1');
            if ($elo_warn !== null) {
                $redirect = add_query_arg('elo_warn', rawurlencode($elo_warn), $redirect);
            }
            wp_safe_redirect($redirect);
            exit;
        }

        // Parziale esplicito o auto quando il match non è ancora deciso.
        if ($save_type === 'partial' || ! $is_complete || $winner === null) {
            TP_Match::save_live_partial($match_id, $sets);
            wp_safe_redirect(admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id . '&partial_saved=1'));
            exit;
        }

        // Auto: set sufficienti per chiudere la partita.
        $elo_warn = TP_Match::save_result($match_id, $winner, $sets);

        if (($match['phase'] ?? '') === 'knockout') {
            TP_Knockout_Service::after_match_resolved($match_id);
        } elseif ((int) ($match['group_id'] ?? 0) > 0) {
            TP_Standings_Service::recalculate_group($tournament_id, (int) $match['group_id']);
        }

        $redirect = admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id . '&result_saved=1');
        if ($elo_warn !== null) {
            $redirect = add_query_arg('elo_warn', rawurlencode($elo_warn), $redirect);
        }
        wp_safe_redirect($redirect);
        exit;
    }
}

// Pagina modifica registrata come callback dinamico
add_action('admin_menu', static function (): void {
    add_submenu_page(
        null,
        __('Modifica torneo', 'tornei-padel'),
        __('Modifica torneo', 'tornei-padel'),
        TP_Roles::CAP_EDIT,
        'tp-tournament-edit',
        [TP_Tournaments_Page::class, 'render_edit']
    );
}, 99);
