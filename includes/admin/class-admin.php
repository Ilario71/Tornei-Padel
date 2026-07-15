<?php
/**
 * Area amministrazione WordPress.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

require_once TP_PLUGIN_DIR . 'includes/admin/class-tournaments-page.php';
require_once TP_PLUGIN_DIR . 'includes/admin/class-dashboard.php';

final class TP_Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        TP_Tournaments_Page::register_handlers();
        TP_Dashboard::init();
    }

    public static function register_menu(): void
    {
        add_menu_page(
            __('Tornei Padel', 'tornei-padel'),
            __('Tornei Padel', 'tornei-padel'),
            TP_Roles::CAP_EDIT,
            'tp-tournaments',
            [TP_Tournaments_Page::class, 'render_list'],
            'dashicons-awards',
            26
        );

        add_submenu_page(
            'tp-tournaments',
            __('Tutti i tornei', 'tornei-padel'),
            __('Tutti i tornei', 'tornei-padel'),
            TP_Roles::CAP_EDIT,
            'tp-tournaments',
            [TP_Tournaments_Page::class, 'render_list']
        );

        add_submenu_page(
            'tp-tournaments',
            __('Nuovo torneo', 'tornei-padel'),
            __('Nuovo torneo', 'tornei-padel'),
            TP_Roles::CAP_MANAGE,
            'tp-tournament-new',
            [TP_Tournaments_Page::class, 'render_create']
        );
    }

    public static function enqueue_assets(string $hook): void
    {
        if (! str_contains($hook, 'tp-tournament')) {
            return;
        }

        wp_enqueue_style(
            'tp-admin',
            TP_PLUGIN_URL . 'admin/css/admin.css',
            [],
            TP_VERSION
        );

        wp_enqueue_media();

        wp_enqueue_script(
            'tp-admin',
            TP_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery'],
            TP_VERSION,
            true
        );

        wp_localize_script('tp-admin', 'tpAdmin', [
            'restUrl'   => rest_url('tornei-padel/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'i18n'      => [
                'noResults'  => __('Nessun giocatore trovato', 'tornei-padel'),
                'searching'  => __('Ricerca…', 'tornei-padel'),
                'typeToSearch' => __('Digita almeno 2 caratteri', 'tornei-padel'),
                'removeTeam' => __('Rimuovi', 'tornei-padel'),
                'teamRequired' => __('Inserisci entrambi i giocatori.', 'tornei-padel'),
            ],
        ]);
    }
}
