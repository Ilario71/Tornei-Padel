<?php
/**
 * Calcolo ranking ELO dopo i match conclusi.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Elo_Rating_Service
{
    public const START_ELO = 1000;

    private const K_FACTOR_NEW = 32;
    private const K_FACTOR_ESTABLISHED = 24;
    private const ESTABLISHED_MATCHES = 20;

    /**
     * Aggiorna ELO dopo un match concluso. Idempotente: ricalcola se già processato.
     *
     * @return bool true se almeno un giocatore è stato aggiornato in elo_history
     */
    public static function process_match(int $match_id): bool
    {
        self::assert_ranking_tables_exist();

        if (TP_Elo_History::exists_for_match($match_id)) {
            TP_Elo_History::revert_match($match_id);
        }

        $match = TP_Match::find($match_id);
        if ($match === null) {
            return false;
        }

        $status = (string) ($match['status'] ?? '');
        if (! in_array($status, ['finished', 'walkover', 'retired'], true)) {
            return false;
        }

        $winner_team_id = $match['winner_team_id'] !== null ? (int) $match['winner_team_id'] : 0;
        $team1_id       = $match['team1_id'] !== null ? (int) $match['team1_id'] : 0;
        $team2_id       = $match['team2_id'] !== null ? (int) $match['team2_id'] : 0;
        if ($winner_team_id === 0 || $team1_id === 0 || $team2_id === 0) {
            return false;
        }

        $sides = self::resolve_sides($team1_id, $team2_id);
        if ($sides === null) {
            return false;
        }

        $side1_won = $winner_team_id === $team1_id;
        self::update_side($match_id, $sides['side1'], $sides['side2'], $side1_won);
        self::update_side($match_id, $sides['side2'], $sides['side1'], ! $side1_won);

        return TP_Elo_History::exists_for_match($match_id);
    }

    /**
     * Annulla ELO se il match non è più concluso (es. salvataggio parziale).
     */
    public static function revert_if_processed(int $match_id): void
    {
        if (TP_Elo_History::exists_for_match($match_id)) {
            TP_Elo_History::revert_match($match_id);
        }
    }

    /**
     * @return array{side1: list<int>, side2: list<int>}|null ranking_player ids
     */
    private static function resolve_sides(int $team1_id, int $team2_id): ?array
    {
        $side1 = self::players_from_team($team1_id);
        $side2 = self::players_from_team($team2_id);
        if ($side1 === [] || $side2 === []) {
            return null;
        }

        return ['side1' => $side1, 'side2' => $side2];
    }

    /** @return list<int> */
    private static function players_from_team(int $team_id): array
    {
        return array_values(array_unique(self::ranking_ids_from_names(self::individual_names_from_team_row(TP_Team::find($team_id)))));
    }

    /**
     * @param array<string, mixed>|null $team
     * @return list<string>
     */
    private static function individual_names_from_team_row(?array $team): array
    {
        if ($team === null) {
            return [];
        }

        $notes = (string) ($team['notes'] ?? '');
        if ($notes === 'american_player') {
            $name = trim((string) ($team['team_name'] ?? ''));

            return ($name !== '' && ! self::is_placeholder_name($name)) ? [$name] : [];
        }

        $names = [];
        foreach (['player1_name', 'player2_name'] as $field) {
            $name = trim((string) ($team[$field] ?? ''));
            if ($name !== '' && ! self::is_placeholder_name($name)) {
                $names[] = $name;
            }
        }
        if ($names !== []) {
            return $names;
        }

        if ($notes === 'american_pair') {
            return self::names_from_display_label((string) ($team['team_name'] ?? ''));
        }

        return [];
    }

    /** @return list<string> */
    private static function names_from_display_label(string $label): array
    {
        $label = trim($label);
        if ($label === '' || self::is_placeholder_name($label)) {
            return [];
        }

        if (preg_match('/\s[\/&,+\-–]\s/u', $label)) {
            $parts = preg_split('/\s*[\/&,+\-–]\s*/u', $label) ?: [];
            $names = [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '' && ! self::is_placeholder_name($part)) {
                    $names[] = $part;
                }
            }
            if (count($names) >= 2) {
                return $names;
            }
        }

        return [];
    }

    /**
     * @param list<string> $names
     * @return list<int>
     */
    private static function ranking_ids_from_names(array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            if (self::is_placeholder_name($name)) {
                continue;
            }
            try {
                $ids[] = TP_Ranking_Player::find_or_create_by_name($name);
            } catch (Throwable $e) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('tp elo ranking player: ' . $e->getMessage());
            }
        }

        return $ids;
    }

    /**
     * @param list<int> $our_side
     * @param list<int> $opp_side
     */
    private static function update_side(int $match_id, array $our_side, array $opp_side, bool $won): void
    {
        $our_elo   = self::average_elo($our_side);
        $opp_elo   = self::average_elo($opp_side);
        $expected  = self::expected_score($our_elo, $opp_elo);
        $actual    = $won ? 1.0 : 0.0;

        foreach (array_values(array_unique($our_side)) as $player_id) {
            $player = TP_Ranking_Player::find($player_id);
            if ($player === null) {
                continue;
            }
            $k = (int) ($player['matches_played'] ?? 0) >= self::ESTABLISHED_MATCHES
                ? self::K_FACTOR_ESTABLISHED
                : self::K_FACTOR_NEW;
            $before = (int) $player['elo_rating'];
            $delta  = (int) round($k * ($actual - $expected));
            $after  = max(100, $before + $delta);

            TP_Elo_History::record($match_id, $player_id, $before, $after, $delta, $won);
            TP_Ranking_Player::apply_match_result($player_id, $after, $won);
        }
    }

    /** @param list<int> $player_ids */
    private static function average_elo(array $player_ids): float
    {
        if ($player_ids === []) {
            return (float) self::START_ELO;
        }

        $sum = 0.0;
        foreach ($player_ids as $id) {
            $p = TP_Ranking_Player::find($id);
            $sum += (float) ($p['elo_rating'] ?? self::START_ELO);
        }

        return $sum / count($player_ids);
    }

    private static function expected_score(float $elo_a, float $elo_b): float
    {
        return 1.0 / (1.0 + 10.0 ** (($elo_b - $elo_a) / 400.0));
    }

    private static function is_placeholder_name(string $name): bool
    {
        $n = mb_strtolower(trim($name));
        if ($n === '' || in_array($n, ['da indicare', '-', 'tbd', 'n/a'], true)) {
            return true;
        }

        return (bool) preg_match('/^(coppia|squadra|team)\s*\d+$/u', $n);
    }

    private static function assert_ranking_tables_exist(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }

        global $wpdb;

        $players = TP_Database::table('ranking_players');
        $history = TP_Database::table('elo_history');

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->get_var("SELECT id FROM {$players} LIMIT 1");
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->get_var("SELECT id FROM {$history} LIMIT 1");

        if ($wpdb->last_error !== '') {
            throw new RuntimeException(
                'Tabelle ranking non trovate. Riattiva il plugin Tornei Padel per creare le tabelle ELO.'
            );
        }

        $checked = true;
    }
}
