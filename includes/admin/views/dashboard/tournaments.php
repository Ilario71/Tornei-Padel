<?php
/**
 * Widget bacheca — tornei in programma.
 *
 * @package TorneiPadel
 *
 * @var list<array<string, mixed>> $tournaments
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<?php if ($tournaments === []) : ?>
    <p class="tp-dash-empty">
        <?php esc_html_e('Nessun torneo in programma. Creane uno per iniziare.', 'tornei-padel'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=tp-tournament-new')); ?>"><?php esc_html_e('Nuovo torneo', 'tornei-padel'); ?></a>
    </p>
<?php else : ?>
    <table class="widefat striped tp-dash-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Torneo', 'tornei-padel'); ?></th>
                <th><?php esc_html_e('Date', 'tornei-padel'); ?></th>
                <th><?php esc_html_e('Formato', 'tornei-padel'); ?></th>
                <th><?php esc_html_e('Squadre', 'tornei-padel'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tournaments as $tournament) : ?>
                <?php
                $id = (int) ($tournament['id'] ?? 0);
                ?>
                <tr>
                    <td>
                        <strong>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=tp-tournament-edit&id=' . $id)); ?>">
                                <?php echo esc_html((string) ($tournament['name'] ?? '')); ?>
                            </a>
                        </strong>
                    </td>
                    <td>
                        <?php
                        echo esc_html(
                            mysql2date(get_option('date_format'), (string) ($tournament['start_date'] ?? ''))
                            . ' – '
                            . mysql2date(get_option('date_format'), (string) ($tournament['end_date'] ?? ''))
                        );
                        ?>
                    </td>
                    <td><?php echo esc_html(TP_Dashboard::formula_label((string) ($tournament['formula'] ?? ''))); ?></td>
                    <td><?php echo esc_html((string) TP_Team::count_for_tournament($id)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
