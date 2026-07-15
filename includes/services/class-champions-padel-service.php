<?php
/**
 * Torneo Champion's Padel: 32 squadre, 8 gironi da 4, eliminazione stile UEFA Champions League.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Champions_Padel_Service
{
    public const FORMULA = 'champions_padel';

    public const NUM_TEAMS = 32;

    public const NUM_GROUPS = 8;

    public const TEAMS_PER_GROUP = 4;

    public const QUALIFIED_PER_GROUP = 2;

    /** @param array<string, mixed>|null $tournament */
    public static function is_champions_padel(?array $tournament): bool
    {
        return $tournament !== null && (string) ($tournament['formula'] ?? '') === self::FORMULA;
    }

    /** @return array{qualification_mode: string, qualification_count: int, qualification_silver_from: string} */
    public static function default_phase_choice(): array
    {
        return [
            'qualification_mode'        => 'gold_only',
            'qualification_count'       => self::QUALIFIED_PER_GROUP,
            'qualification_silver_from' => 'none',
        ];
    }

    /** @return array{sets_to_win: int, games_per_set: int, match_duration: int, break_duration: int} */
    public static function default_match_format(): array
    {
        return [
            'sets_to_win'     => 2,
            'games_per_set'   => 6,
            'match_duration'  => 90,
            'break_duration'  => 15,
        ];
    }

    /**
     * @throws RuntimeException
     */
    public static function bootstrap_group_phase(int $tournament_id): void
    {
        $t = TP_Tournament::find($tournament_id);
        if ($t === null || ! self::is_champions_padel($t)) {
            throw new RuntimeException(__('Torneo Champion\'s Padel non trovato.', 'tornei-padel'));
        }

        $teams = TP_Team::for_tournament($tournament_id);
        $count = count($teams);
        if ($count !== self::NUM_TEAMS) {
            throw new RuntimeException(
                sprintf(
                    /* translators: 1: required teams, 2: current teams */
                    __('Per Champion\'s Padel servono esattamente %1$d squadre iscritte (attualmente: %2$d).', 'tornei-padel'),
                    self::NUM_TEAMS,
                    $count
                )
            );
        }

        TP_Group_Draw_Service::draw_champions_groups($tournament_id);
    }

    public static function rules_summary_html(): string
    {
        return '<ul class="tp-rules-list">'
            . '<li><strong>32 squadre</strong> in <strong>8 gironi</strong> da 4 (tutti contro tutti).</li>'
            . '<li>' . esc_html__('Passano le prime 2 di ogni girone agli ottavi (16 squadre).', 'tornei-padel') . '</li>'
            . '<li>' . esc_html__('Tabellone eliminatorio con accoppiamenti stile Champions League (1º girone A vs 2º girone B, ecc.).', 'tornei-padel') . '</li>'
            . '<li>' . esc_html__('Partite al meglio dei 3 set; set al 6, a 5-5 si va al 7, a 6-6 super tie-break a 7 punti (+2 oltre il 6-6).', 'tornei-padel') . '</li>'
            . '</ul>';
    }

    public static function scoring_hint(): string
    {
        return __('Al meglio di 3 set. Set al 6 game; a 5-5 si gioca il 7° game; a 6-6 super tie-break (STB) a 7 punti, con vantaggio di 2 se si resta in parità oltre il 6-6.', 'tornei-padel');
    }
}
