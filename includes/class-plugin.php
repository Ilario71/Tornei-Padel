<?php
/**
 * Bootstrap del plugin.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init(): void
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'maybe_upgrade_db']);
        add_action('init', [$this, 'maybe_flush_rewrites'], 99);

        TP_Plugin_Info::init();
        TP_Roles::register();
        TP_Admin::init();
        TP_Public::init();
        TP_Shortcodes::init();
        TP_REST_Controller::init();

        if (class_exists('TP_Player_Notification_Hooks')) {
            TP_Player_Notification_Hooks::init();
        }
    }

    public function maybe_flush_rewrites(): void
    {
        $stored = get_option('tp_plugin_version', '');
        if ($stored === TP_VERSION) {
            return;
        }

        // Differito a shutdown: evita timeout/fatal durante admin-post.php (sorteggio, salva, ecc.).
        add_action('shutdown', static function (): void {
            if (get_option('tp_plugin_version', '') === TP_VERSION) {
                return;
            }
            flush_rewrite_rules(false);
            update_option('tp_plugin_version', TP_VERSION);
        }, 99);
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('tornei-padel', false, dirname(plugin_basename(TP_PLUGIN_FILE)) . '/languages');
    }

    public function maybe_upgrade_db(): void
    {
        $installed = (string) get_option('tp_db_version', '');

        if ($installed === '') {
            TP_Activator::create_tables();
            TP_Activator::ensure_schema();
            update_option('tp_db_version', TP_DB_VERSION);

            return;
        }

        if ($installed !== TP_DB_VERSION) {
            TP_Activator::run_migrations($installed);
            update_option('tp_db_version', TP_DB_VERSION);
        }

        TP_Activator::ensure_schema();
    }
}
