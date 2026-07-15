<?php
/**
 * Attivazione plugin e schema database.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Activator
{
    public static function activate(): void
    {
        self::create_tables();
        self::ensure_schema();
        TP_Roles::add_roles();
        TP_Roles::add_caps();
        update_option('tp_db_version', TP_DB_VERSION);
        update_option('tp_plugin_version', TP_VERSION);
        flush_rewrite_rules();
    }

    public static function ensure_schema(): void
    {
        self::ensure_poster_column();
        self::ensure_courts_table();
        self::ensure_match_court_column();
        self::ensure_player_notifications_table();
    }

    /**
     * Migrazioni leggere tra versioni schema (senza rilanciare create_tables completo).
     */
    public static function run_migrations(string $from_version): void
    {
        if ($from_version === '' || version_compare($from_version, '0.1.4', '<')) {
            self::ensure_player_notifications_table();
        }
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    public static function create_tables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $t       = static fn (string $s): string => TP_Database::table($s);

        $sql = [];

        $sql[] = "CREATE TABLE {$t('tournaments')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL,
            description TEXT NULL,
            poster_id BIGINT UNSIGNED NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            num_teams SMALLINT UNSIGNED NOT NULL DEFAULT 8,
            num_groups TINYINT UNSIGNED NOT NULL DEFAULT 2,
            formula VARCHAR(50) NOT NULL DEFAULT 'groups_knockout',
            qualification_mode VARCHAR(20) NOT NULL DEFAULT 'gold_only',
            qualification_count TINYINT UNSIGNED NOT NULL DEFAULT 2,
            qualification_silver_from VARCHAR(30) NOT NULL DEFAULT 'second_each_group',
            sets_to_win TINYINT UNSIGNED NOT NULL DEFAULT 2,
            games_per_set TINYINT UNSIGNED NOT NULL DEFAULT 6,
            match_duration SMALLINT UNSIGNED NOT NULL DEFAULT 60,
            break_duration SMALLINT UNSIGNED NOT NULL DEFAULT 15,
            daily_start_time TIME NOT NULL DEFAULT '09:00:00',
            daily_end_time TIME NOT NULL DEFAULT '22:00:00',
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY created_by (created_by)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('tournament_settings')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT UNSIGNED NOT NULL,
            live_enabled TINYINT(1) NOT NULL DEFAULT 1,
            silver_enabled TINYINT(1) NOT NULL DEFAULT 0,
            american_scoring_mode VARCHAR(20) NULL,
            american_time_minutes SMALLINT UNSIGNED NULL,
            american_points_target SMALLINT UNSIGNED NULL,
            match_category VARCHAR(20) NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tournament_id (tournament_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('tournament_organizers')} (
            tournament_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (tournament_id, user_id),
            KEY user_id (user_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('teams')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT UNSIGNED NOT NULL,
            team_name VARCHAR(160) NOT NULL,
            team_type VARCHAR(20) NOT NULL DEFAULT 'double',
            player1_name VARCHAR(120) NOT NULL,
            player2_name VARCHAR(120) NOT NULL,
            phone VARCHAR(40) NULL,
            email VARCHAR(190) NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tournament_id (tournament_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('groups')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(50) NOT NULL,
            sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY tournament_id (tournament_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('group_teams')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id BIGINT UNSIGNED NOT NULL,
            team_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY group_team (group_id, team_id),
            KEY team_id (team_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('courts')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(80) NOT NULL,
            indoor TINYINT(1) NOT NULL DEFAULT 1,
            active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY tournament_id (tournament_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('matches')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL DEFAULT 'group',
            bracket_type VARCHAR(20) NOT NULL DEFAULT 'none',
            group_id BIGINT UNSIGNED NULL,
            team1_id BIGINT UNSIGNED NULL,
            team2_id BIGINT UNSIGNED NULL,
            winner_team_id BIGINT UNSIGNED NULL,
            court_id BIGINT UNSIGNED NULL,
            match_date DATE NULL,
            start_time TIME NULL,
            end_time TIME NULL,
            round_name VARCHAR(80) NULL,
            round_index SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            bracket_slot SMALLINT UNSIGNED NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
            result_locked TINYINT(1) NOT NULL DEFAULT 0,
            walkover_team_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tournament_id (tournament_id),
            KEY group_id (group_id),
            KEY court_id (court_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('match_sets')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            match_id BIGINT UNSIGNED NOT NULL,
            set_number TINYINT UNSIGNED NOT NULL,
            team1_score SMALLINT NOT NULL DEFAULT 0,
            team2_score SMALLINT NOT NULL DEFAULT 0,
            is_super_tiebreak TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY match_set (match_id, set_number),
            KEY match_id (match_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('standings')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT UNSIGNED NOT NULL,
            group_id BIGINT UNSIGNED NOT NULL,
            team_id BIGINT UNSIGNED NOT NULL,
            points SMALLINT NOT NULL DEFAULT 0,
            wins SMALLINT NOT NULL DEFAULT 0,
            losses SMALLINT NOT NULL DEFAULT 0,
            sets_won SMALLINT NOT NULL DEFAULT 0,
            sets_lost SMALLINT NOT NULL DEFAULT 0,
            games_won SMALLINT NOT NULL DEFAULT 0,
            games_lost SMALLINT NOT NULL DEFAULT 0,
            rank_pos TINYINT UNSIGNED NULL,
            PRIMARY KEY (id),
            UNIQUE KEY standing (tournament_id, group_id, team_id),
            KEY group_id (group_id),
            KEY team_id (team_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('ranking_players')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            display_name VARCHAR(160) NOT NULL,
            name_key VARCHAR(160) NOT NULL,
            elo_rating INT NOT NULL DEFAULT 1000,
            matches_played INT UNSIGNED NOT NULL DEFAULT 0,
            wins INT UNSIGNED NOT NULL DEFAULT 0,
            losses INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY name_key (name_key),
            KEY elo_rating (elo_rating)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('elo_history')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            match_id BIGINT UNSIGNED NOT NULL,
            ranking_player_id BIGINT UNSIGNED NOT NULL,
            elo_before INT NOT NULL,
            elo_after INT NOT NULL,
            elo_delta INT NOT NULL,
            won TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY match_id (match_id),
            KEY ranking_player_id (ranking_player_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$t('player_notifications')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(40) NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NULL,
            link VARCHAR(500) NULL,
            read_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY read_at (read_at)
        ) $charset;";

        foreach ($sql as $statement) {
            dbDelta($statement);
        }
    }

    public static function ensure_poster_column(): void
    {
        global $wpdb;

        $table = TP_Database::table('tournaments');
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $column = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'poster_id'");

        if ($column === [] || $column === null) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN poster_id BIGINT UNSIGNED NULL AFTER description");
        }
    }

    public static function ensure_courts_table(): void
    {
        global $wpdb;

        $table = TP_Database::table('courts');
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

        if ($exists !== $table) {
            self::create_tables();
        }
    }

    public static function ensure_match_court_column(): void
    {
        global $wpdb;

        $table = TP_Database::table('matches');
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $column = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'court_id'");

        if ($column === [] || $column === null) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN court_id BIGINT UNSIGNED NULL AFTER winner_team_id");
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query("ALTER TABLE {$table} ADD KEY court_id (court_id)");
        }
    }

    public static function ensure_player_notifications_table(): void
    {
        global $wpdb;

        $table = TP_Database::table('player_notifications');
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

        if ($exists === $table) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        dbDelta(
            "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(40) NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NULL,
            link VARCHAR(500) NULL,
            read_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY read_at (read_at)
        ) $charset;"
        );
    }
}
