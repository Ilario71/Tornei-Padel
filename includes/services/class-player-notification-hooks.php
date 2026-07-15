<?php
/**
 * Hook per generare notifiche giocatore.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Player_Notification_Hooks
{
    public static function init(): void
    {
        add_action('tp_team_registered', [self::class, 'on_team_registered'], 10, 2);
        add_action('tp_match_finished', [self::class, 'on_match_finished'], 10, 1);
    }

    /** @param array<string, mixed> $team */
    public static function on_team_registered(int $tournament_id, array $team): void
    {
        TP_Player_Notification::notify_team_registered($tournament_id, $team);
    }

    public static function on_match_finished(int $match_id): void
    {
        TP_Player_Notification::notify_match_result($match_id);
    }
}
