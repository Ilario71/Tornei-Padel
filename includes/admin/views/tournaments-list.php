<?php
/**
 * Lista tornei — admin.
 *
 * @package TorneiPadel
 *
 * @var list<array<string, mixed>> $tournaments
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap tp-admin-wrap">
    <h1 class="tp-admin-title">
        <span class="tp-icon">🎾</span>
        <?php esc_html_e('Tornei Padel', 'tornei-padel'); ?>
    </h1>

    <?php if (isset($_GET['deleted'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Torneo eliminato.', 'tornei-padel'); ?></p></div>
    <?php elseif (isset($_GET['delete_error'])) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Impossibile eliminare il torneo.', 'tornei-padel'); ?></p></div>
    <?php endif; ?>

    <a href="<?php echo esc_url(admin_url('admin.php?page=tp-tournament-new')); ?>" class="page-title-action">
        <?php esc_html_e('Nuovo torneo', 'tornei-padel'); ?>
    </a>

    <table class="wp-list-table widefat fixed striped tp-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Nome', 'tornei-padel'); ?></th>
                <th><?php esc_html_e('Date', 'tornei-padel'); ?></th>
                <th><?php esc_html_e('Formato', 'tornei-padel'); ?></th>
                <th><?php esc_html_e('Squadre', 'tornei-padel'); ?></th>
                <th><?php esc_html_e('Azioni', 'tornei-padel'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tournaments === []) : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('Nessun torneo ancora. Creane uno!', 'tornei-padel'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($tournaments as $t) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($t['name']); ?></strong></td>
                        <td>
                            <?php
                            echo esc_html(
                                mysql2date(get_option('date_format'), $t['start_date'])
                                . ' – '
                                . mysql2date(get_option('date_format'), $t['end_date'])
                            );
                            ?>
                        </td>
                        <td><code><?php echo esc_html($t['formula']); ?></code></td>
                        <td><?php echo esc_html((string) TP_Team::count_for_tournament((int) $t['id'])); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=tp-tournament-edit&id=' . (int) $t['id'])); ?>">
                                <?php esc_html_e('Gestisci', 'tornei-padel'); ?>
                            </a>
                            <?php if (TP_Roles::can_manage()) : ?>
                                <span aria-hidden="true">|</span>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="tp-inline-delete tp-delete-tournament-form">
                                    <?php wp_nonce_field('tp_delete_tournament_' . (int) $t['id']); ?>
                                    <input type="hidden" name="action" value="tp_delete_tournament">
                                    <input type="hidden" name="tournament_id" value="<?php echo esc_attr((string) $t['id']); ?>">
                                    <button type="submit" class="button-link-delete" onclick="return confirm('<?php echo esc_js(sprintf(__('Eliminare definitivamente il torneo «%s»? Questa azione non può essere annullata.', 'tornei-padel'), $t['name'])); ?>');">
                                        <?php esc_html_e('Elimina', 'tornei-padel'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
