<?php
/**
 * Tabella partite — admin partial.
 *
 * @package TorneiPadel
 *
 * @var list<array<string, mixed>> $matches
 * @var int $tid
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<table class="wp-list-table widefat fixed striped tp-table">
    <thead>
        <tr>
            <th><?php esc_html_e('Turno / fase', 'tornei-padel'); ?></th>
            <th><?php esc_html_e('Campo', 'tornei-padel'); ?></th>
            <th><?php esc_html_e('Squadre', 'tornei-padel'); ?></th>
            <th><?php esc_html_e('Stato', 'tornei-padel'); ?></th>
            <th><?php esc_html_e('Risultato', 'tornei-padel'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($matches as $match) : ?>
            <?php
            $sets    = TP_Match::sets_for_match((int) $match['id']);
            $score_s = TP_Match_Result_Service::format_score_label($sets);
            $round   = (string) ($match['round_name'] ?? '');
            if ($round === '' && ($match['phase'] ?? '') === 'group') {
                $round = __('Girone', 'tornei-padel');
            }
            ?>
            <tr>
                <td>
                    <?php if ($round !== '') : ?>
                        <span class="tp-round-label"><?php echo esc_html($round); ?></span>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $court_name = (string) ($match['court_name'] ?? '');
                    echo $court_name !== '' ? esc_html($court_name) : '—';
                    ?>
                </td>
                <td>
                    <?php echo esc_html(($match['team1_name'] ?? 'TBD') . ' vs ' . ($match['team2_name'] ?? 'TBD')); ?>
                </td>
                <td>
                    <span class="tp-status tp-status--<?php echo esc_attr($match['status']); ?>">
                        <?php echo esc_html($match['status']); ?>
                    </span>
                </td>
                <td>
                    <?php if ($match['status'] === 'finished') : ?>
                        <?php echo esc_html($score_s); ?>
                        <?php if (! empty($match['winner_name'])) : ?>
                            <br><small>→ <?php echo esc_html($match['winner_name']); ?></small>
                        <?php endif; ?>
                    <?php elseif ($match['status'] === 'live' && TP_Roles::can_enter_results()) : ?>
                        <?php $live_mode = true; include TP_PLUGIN_DIR . 'includes/admin/views/partials/match-result-form.php'; ?>
                    <?php elseif ($match['status'] === 'live') : ?>
                        <?php echo esc_html($score_s !== '' ? $score_s : '—'); ?>
                    <?php elseif (TP_Roles::can_enter_results()) : ?>
                        <?php $live_mode = false; include TP_PLUGIN_DIR . 'includes/admin/views/partials/match-result-form.php'; ?>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
