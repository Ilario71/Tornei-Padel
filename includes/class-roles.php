<?php
/**
 * Ruoli e capability WordPress.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Roles
{
    public const CAP_MANAGE = 'tp_manage_tournaments';
    public const CAP_EDIT   = 'tp_edit_tournaments';
    public const CAP_ENTER  = 'tp_enter_results';
    public const CAP_VIEW   = 'tp_view_tournaments';

    public static function register(): void
    {
        add_action('init', [self::class, 'add_caps'], 20);
    }

    public static function add_roles(): void
    {
        add_role(
            'tp_organizer',
            __('Organizzatore tornei', 'tornei-padel'),
            [
                'read'           => true,
                self::CAP_VIEW   => true,
                self::CAP_EDIT   => true,
                self::CAP_ENTER  => true,
            ]
        );

        add_role(
            'tp_player',
            __('Giocatore padel', 'tornei-padel'),
            [
                'read'         => true,
                self::CAP_VIEW => true,
            ]
        );
    }

    public static function add_caps(): void
    {
        $admin = get_role('administrator');
        if ($admin) {
            foreach ([self::CAP_MANAGE, self::CAP_EDIT, self::CAP_ENTER, self::CAP_VIEW] as $cap) {
                $admin->add_cap($cap);
            }
        }

        $organizer = get_role('tp_organizer');
        if ($organizer) {
            foreach ([self::CAP_VIEW, self::CAP_EDIT, self::CAP_ENTER] as $cap) {
                $organizer->add_cap($cap);
            }
        }

        $player = get_role('tp_player');
        if ($player) {
            $player->add_cap(self::CAP_VIEW);
        }
    }

    public static function can_manage(): bool
    {
        return current_user_can(self::CAP_MANAGE) || current_user_can('manage_options');
    }

    public static function can_edit(): bool
    {
        return self::can_manage() || current_user_can(self::CAP_EDIT);
    }

    public static function can_enter_results(): bool
    {
        return self::can_edit() || current_user_can(self::CAP_ENTER);
    }
}
