<?php
/**
 * Shortcode frontend.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Shortcodes
{
    public static function init(): void
    {
        add_shortcode('tornei_padel_lista', [self::class, 'tournament_list']);
        add_shortcode('tornei_padel_live', [self::class, 'live_hub']);
        add_shortcode('tornei_padel_ranking', [self::class, 'ranking']);
    }

    /** @param array<string, string> $atts */
    public static function tournament_list(array $atts = []): string
    {
        wp_enqueue_style('tp-public');

        $tournaments = TP_Tournament::all((int) ($atts['limit'] ?? 20));

        ob_start();
        include TP_PLUGIN_DIR . 'includes/public/views/shortcode-list.php';

        return (string) ob_get_clean();
    }

    public static function live_hub(): string
    {
        wp_enqueue_style('tp-public');

        $tournaments = TP_Tournament::live_enabled();

        ob_start();
        include TP_PLUGIN_DIR . 'includes/public/views/shortcode-live-hub.php';

        return (string) ob_get_clean();
    }

    public static function ranking(): string
    {
        wp_enqueue_style('tp-public');

        $rows  = TP_Ranking_Player::leaderboard(50);
        $tiers = TP_Ranking_Level_Service::all_tiers();

        ob_start();
        include TP_PLUGIN_DIR . 'includes/public/views/shortcode-ranking.php';

        return (string) ob_get_clean();
    }
}
