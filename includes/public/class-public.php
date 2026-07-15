<?php
/**
 * Frontend pubblico.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Public
{
    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('init', [self::class, 'register_rewrite_rules']);
        add_filter('query_vars', [self::class, 'add_query_vars']);
        add_filter('body_class', [self::class, 'body_classes']);
        add_action('template_redirect', [self::class, 'maybe_render_live_page'], 1);
    }

    /**
     * Pagine pubbliche del plugin (live, tornei, ranking e future pagine con shortcode).
     */
    public static function is_app_page(): bool
    {
        if (get_query_var('tp_live')) {
            return true;
        }

        if (! is_singular('page')) {
            return false;
        }

        $post = get_queried_object();
        if (! $post instanceof WP_Post) {
            return false;
        }

        foreach (self::app_shortcodes() as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public static function app_shortcodes(): array
    {
        return [
            'tornei_padel_lista',
            'tornei_padel_live',
            'tornei_padel_ranking',
        ];
    }

    /** @param list<string> $classes */
    public static function body_classes(array $classes): array
    {
        if (self::is_app_page()) {
            $classes[] = 'tp-app-page';
        }

        return $classes;
    }

    public static function enqueue_assets(): void
    {
        wp_register_style(
            'tp-public-fonts',
            'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap',
            [],
            null
        );
        wp_register_style('tp-public', TP_PLUGIN_URL . 'public/css/tornei-padel.css', ['tp-public-fonts'], TP_VERSION);
        wp_register_script('tp-live', TP_PLUGIN_URL . 'public/js/live.js', [], TP_VERSION, true);
        wp_register_script('tp-match-live', TP_PLUGIN_URL . 'public/js/match-live.js', [], TP_VERSION, true);
    }

    public static function register_rewrite_rules(): void
    {
        add_rewrite_rule(
            '^torneo-live/([^/]+)/partita/([0-9]+)/?$',
            'index.php?tp_live=match&tp_slug=$matches[1]&tp_match_id=$matches[2]',
            'top'
        );
        add_rewrite_rule('^torneo-live/?$', 'index.php?tp_live=hub', 'top');
        add_rewrite_rule('^torneo-live/([^/]+)/?$', 'index.php?tp_live=show&tp_slug=$matches[1]', 'top');
    }

    /** @param list<string> $vars */
    public static function add_query_vars(array $vars): array
    {
        $vars[] = 'tp_live';
        $vars[] = 'tp_slug';
        $vars[] = 'tp_match_id';

        return $vars;
    }

    /** @return array{mode: string, slug: string, match_id: int}|null */
    private static function resolve_live_route(): ?array
    {
        $mode = get_query_var('tp_live');
        if (is_string($mode) && $mode !== '') {
            $slug     = get_query_var('tp_slug');
            $match_id = (int) get_query_var('tp_match_id');

            return [
                'mode'     => $mode,
                'slug'     => is_string($slug) ? $slug : '',
                'match_id' => $match_id,
            ];
        }

        $path = self::request_path();
        if ($path === '') {
            return null;
        }

        if (preg_match('#^torneo-live/([^/]+)/partita/([0-9]+)/?$#', $path, $matches) === 1) {
            return [
                'mode'     => 'match',
                'slug'     => sanitize_title($matches[1]),
                'match_id' => (int) $matches[2],
            ];
        }

        if (preg_match('#^torneo-live/([^/]+)/?$#', $path, $matches) === 1) {
            return [
                'mode'     => 'show',
                'slug'     => sanitize_title($matches[1]),
                'match_id' => 0,
            ];
        }

        if ($path === 'torneo-live') {
            return [
                'mode'     => 'hub',
                'slug'     => '',
                'match_id' => 0,
            ];
        }

        return null;
    }

    private static function request_path(): string
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = trim((string) parse_url($uri, PHP_URL_PATH), '/');

        if ($path === '') {
            return '';
        }

        $home_path = trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
        if ($home_path !== '' && str_starts_with($path, $home_path . '/')) {
            $path = trim(substr($path, strlen($home_path)), '/');
        } elseif ($home_path !== '' && $path === $home_path) {
            $path = '';
        }

        return $path;
    }

    public static function maybe_render_live_page(): void
    {
        $route = self::resolve_live_route();
        if ($route === null) {
            return;
        }

        wp_enqueue_style('tp-public');

        if ($route['mode'] === 'hub') {
            include TP_PLUGIN_DIR . 'includes/public/views/live-hub.php';
            exit;
        }

        if ($route['mode'] === 'show') {
            $slug       = sanitize_title($route['slug']);
            $tournament = TP_Tournament::find_by_slug($slug);

            if ($tournament === null) {
                status_header(404);
                nocache_headers();
                include get_query_template('404');
                exit;
            }

            wp_enqueue_script('tp-live');
            include TP_PLUGIN_DIR . 'includes/public/views/live-show.php';
            exit;
        }

        if ($route['mode'] === 'match') {
            $slug       = sanitize_title($route['slug']);
            $match_id   = (int) $route['match_id'];
            $tournament = TP_Tournament::find_by_slug($slug);

            if ($tournament === null || $match_id <= 0) {
                status_header(404);
                nocache_headers();
                include get_query_template('404');
                exit;
            }

            $match = TP_Match::find($match_id);
            if ($match === null || (int) ($match['tournament_id'] ?? 0) !== (int) $tournament['id']) {
                status_header(404);
                nocache_headers();
                include get_query_template('404');
                exit;
            }

            wp_enqueue_script('tp-match-live');
            include TP_PLUGIN_DIR . 'includes/public/views/live-match-show.php';
            exit;
        }
    }
}
