<?php
/**
 * REST API per aggiornamenti live.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_REST_Controller
{
    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void
    {
        register_rest_route('tornei-padel/v1', '/live/(?P<slug>[a-z0-9\-]+)/match/(?P<match_id>[0-9]+)', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'match_live_data'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('tornei-padel/v1', '/live/(?P<slug>[a-z0-9\-]+)', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'live_data'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('tornei-padel/v1', '/players/search', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'search_players'],
            'permission_callback' => static fn (): bool => TP_Roles::can_edit(),
            'args'                => [
                'q' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /** @return WP_REST_Response|WP_Error */
    public static function search_players(WP_REST_Request $request)
    {
        $query = trim((string) $request->get_param('q'));
        if (mb_strlen($query) < 2) {
            return rest_ensure_response(['players' => []]);
        }

        return rest_ensure_response([
            'players' => TP_Player_Search_Service::search($query),
        ]);
    }

    /** @return WP_REST_Response|WP_Error */
    public static function live_data(WP_REST_Request $request)
    {
        $slug = sanitize_title($request->get_param('slug'));
        $tournament = TP_Tournament::find_by_slug($slug);

        if ($tournament === null) {
            return new WP_Error('not_found', __('Torneo non trovato.', 'tornei-padel'), ['status' => 404]);
        }

        $response = rest_ensure_response(self::build_live_payload($tournament));
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }

    /** @return WP_REST_Response|WP_Error */
    public static function match_live_data(WP_REST_Request $request)
    {
        $slug     = sanitize_title($request->get_param('slug'));
        $match_id = (int) $request->get_param('match_id');
        $tournament = TP_Tournament::find_by_slug($slug);

        if ($tournament === null) {
            return new WP_Error('not_found', __('Torneo non trovato.', 'tornei-padel'), ['status' => 404]);
        }

        $match = TP_Match::find($match_id);
        if ($match === null || (int) ($match['tournament_id'] ?? 0) !== (int) $tournament['id']) {
            return new WP_Error('not_found', __('Partita non trovata.', 'tornei-padel'), ['status' => 404]);
        }

        $enriched = TP_Match_Result_Service::attach_sets([$match])[0];
        $payload  = self::format_match_payload($enriched);

        $response = rest_ensure_response([
            'tournament' => [
                'id'   => (int) $tournament['id'],
                'name' => $tournament['name'],
                'slug' => $tournament['slug'],
            ],
            'match'      => $payload,
            'updated_at' => gmdate('c'),
        ]);
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }

    /** @param array<string, mixed> $match */
    private static function format_match_payload(array $match): array
    {
        $sets = is_array($match['sets'] ?? null) ? $match['sets'] : [];

        return [
            'id'           => (int) $match['id'],
            'status'       => (string) ($match['status'] ?? 'scheduled'),
            'round_name'   => (string) ($match['round_name'] ?? ''),
            'court_name'   => (string) ($match['court_name'] ?? ''),
            'team1_name'   => (string) ($match['team1_name'] ?? 'TBD'),
            'team2_name'   => (string) ($match['team2_name'] ?? 'TBD'),
            'team1_players'=> trim(((string) ($match['t1p1'] ?? '')) . ' · ' . ((string) ($match['t1p2'] ?? '')), " ·") ?: '—',
            'team2_players'=> trim(((string) ($match['t2p1'] ?? '')) . ' · ' . ((string) ($match['t2p2'] ?? '')), " ·") ?: '—',
            'score_label'  => (string) ($match['score_label'] ?? ''),
            'sets'         => $sets,
            'team1_scores' => array_map(static fn (array $s): int => (int) $s['team1_score'], $sets),
            'team2_scores' => array_map(static fn (array $s): int => (int) $s['team2_score'], $sets),
        ];
    }

    /** @return array<string, mixed> */
    private static function build_live_payload(array $tournament): array
    {
        $tid     = (int) $tournament['id'];
        $matches = TP_Match_Result_Service::attach_sets(TP_Match::for_tournament($tid));
        $groups  = TP_Group::for_tournament($tid);

        $standings = [];
        foreach ($groups as $group) {
            $standings[$group['name']] = TP_Standings_Service::for_group((int) $group['id']);
        }

        $live_matches = array_values(array_filter(
            $matches,
            static fn ($m) => in_array($m['status'], ['live', 'scheduled'], true)
        ));

        $in_progress = array_values(array_filter(
            $matches,
            static fn ($m) => ($m['status'] ?? '') === 'live'
        ));

        return [
            'tournament' => [
                'id'   => $tid,
                'name' => $tournament['name'],
                'slug' => $tournament['slug'],
            ],
            'standings'    => $standings,
            'matches'      => $matches,
            'live_matches' => $live_matches,
            'in_progress'  => $in_progress,
            'updated_at'   => gmdate('c'),
        ];
    }
}
