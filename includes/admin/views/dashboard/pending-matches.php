<?php
/**
 * Widget bacheca — partite da completare.
 *
 * @package TorneiPadel
 *
 * @var list<array<string, mixed>> $matches
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<?php if ($matches === []) : ?>
    <p class="tp-dash-empty"><?php esc_html_e('Nessuna partita in attesa di risultato. Tutto aggiornato!', 'tornei-padel'); ?></p>
<?php else : ?>
    <ul class="tp-dash-list">
        <?php foreach ($matches as $match) : ?>
            <?php
            $match_id       = (int) ($match['id'] ?? 0);
            $tournament_id  = (int) ($match['tournament_id'] ?? 0);
            $status         = (string) ($match['status'] ?? 'scheduled');
            $phase          = (string) ($match['phase'] ?? '');
            $team1          = (string) ($match['team1_name'] ?? '—');
            $team2          = (string) ($match['team2_name'] ?? '—');
            $date_label     = '';
            $match_date     = (string) ($match['match_date'] ?? '');
            $start_time     = (string) ($match['start_time'] ?? '');

            if ($match_date !== '') {
                $date_label = mysql2date(get_option('date_format'), $match_date);
                if ($start_time !== '') {
                    $date_label .= ' ' . substr($start_time, 0, 5);
                }
            }
            ?>
            <li class="tp-dash-list__item">
                <div class="tp-dash-list__head">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tp-tournament-edit&id=' . $tournament_id . '#match-' . $match_id)); ?>">
                        <?php echo esc_html((string) ($match['tournament_name'] ?? '')); ?>
                    </a>
                    <?php if ($date_label !== '') : ?>
                        <span class="tp-dash-list__meta"><?php echo esc_html($date_label); ?></span>
                    <?php endif; ?>
                </div>
                <p class="tp-dash-list__match">
                    <?php echo esc_html($team1); ?>
                    <span aria-hidden="true"> vs </span>
                    <?php echo esc_html($team2); ?>
                </p>
                <div class="tp-dash-list__badges">
                    <span class="tp-dash-badge tp-dash-badge--<?php echo esc_attr($status); ?>">
                        <?php echo esc_html(TP_Dashboard::status_label($status)); ?>
                    </span>
                    <?php if ($phase !== '') : ?>
                        <span class="tp-dash-badge tp-dash-badge--phase">
                            <?php echo esc_html(TP_Dashboard::phase_label($phase)); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
