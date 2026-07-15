<?php
/**
 * Pagina live singola partita — vista aerea campo.
 *
 * @package TorneiPadel
 *
 * @var array<string, mixed> $tournament
 * @var array<string, mixed> $match
 */

if (! defined('ABSPATH')) {
    exit;
}

$sets        = TP_Match::sets_for_match((int) $match['id']);
$score_label = TP_Match_Result_Service::format_score_label($sets);
$status_labels = [
    'scheduled' => __('Programmata', 'tornei-padel'),
    'live'      => __('IN CORSO', 'tornei-padel'),
    'finished'  => __('Terminata', 'tornei-padel'),
    'walkover'  => __('Walkover', 'tornei-padel'),
    'retired'   => __('Ritiro', 'tornei-padel'),
];
$status_label = $status_labels[$match['status']] ?? (string) $match['status'];
$court_image  = TP_Match::court_image_url();

get_header();
?>
<main class="tp-match-live tp-page" data-tp-match-live>
    <header class="tp-page-header tp-page-header--compact">
        <p class="tp-breadcrumb">
            <a href="<?php echo esc_url(home_url('/torneo-live/' . $tournament['slug'])); ?>">
                ← <?php echo esc_html($tournament['name']); ?>
            </a>
        </p>
        <div class="tp-match-live__headline">
            <?php if (! empty($match['round_name'])) : ?>
                <p class="tp-match-live__round"><?php echo esc_html((string) $match['round_name']); ?></p>
            <?php endif; ?>
            <?php if (! empty($match['court_name'])) : ?>
                <span class="tp-match-court"><?php echo esc_html((string) $match['court_name']); ?></span>
            <?php endif; ?>
            <span class="tp-status-badge tp-status-badge--<?php echo esc_attr((string) $match['status']); ?>">
                <?php echo esc_html($status_label); ?>
            </span>
            <span class="tp-live-pulse" data-tp-match-live-status><?php esc_html_e('Aggiornamento automatico', 'tornei-padel'); ?></span>
        </div>
    </header>

    <section class="tp-match-court-view tp-match-court-view--<?php echo esc_attr((string) $match['status']); ?>" id="tp-match-court-panel">
        <div class="tp-match-court-view__backdrop" aria-hidden="true">
            <img
                class="tp-match-court-view__image"
                src="<?php echo esc_url($court_image); ?>"
                alt=""
                width="1920"
                height="1080"
                decoding="async"
            >
            <div class="tp-match-court-view__shade"></div>
        </div>

        <div class="tp-match-court-view__overlay">
            <div class="tp-match-court-view__half tp-match-court-view__half--left" data-tp-team="1">
                <div class="tp-match-court-view__team">
                    <p class="tp-match-court-view__team-name" data-tp-field="team1_name">
                        <?php echo esc_html((string) ($match['team1_name'] ?? 'TBD')); ?>
                    </p>
                    <p class="tp-match-court-view__players" data-tp-field="team1_players">
                        <?php
                        echo esc_html(
                            trim(
                                ((string) ($match['t1p1'] ?? '')) . ' · ' . ((string) ($match['t1p2'] ?? '')),
                                " ·"
                            ) ?: '—'
                        );
                        ?>
                    </p>
                    <div class="tp-match-court-view__sets" data-tp-field="team1_sets">
                        <?php if ($sets === []) : ?>
                            <span class="tp-match-court-view__set-pill">—</span>
                        <?php else : ?>
                            <?php foreach ($sets as $set) : ?>
                                <span class="tp-match-court-view__set-pill">
                                    <?php echo esc_html((string) (int) $set['team1_score']); ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tp-match-court-view__net" aria-hidden="true">
                <span class="tp-match-court-view__score-total" data-tp-field="score_label">
                    <?php echo esc_html($score_label !== '' ? $score_label : '0-0'); ?>
                </span>
            </div>

            <div class="tp-match-court-view__half tp-match-court-view__half--right" data-tp-team="2">
                <div class="tp-match-court-view__team">
                    <p class="tp-match-court-view__team-name" data-tp-field="team2_name">
                        <?php echo esc_html((string) ($match['team2_name'] ?? 'TBD')); ?>
                    </p>
                    <p class="tp-match-court-view__players" data-tp-field="team2_players">
                        <?php
                        echo esc_html(
                            trim(
                                ((string) ($match['t2p1'] ?? '')) . ' · ' . ((string) ($match['t2p2'] ?? '')),
                                " ·"
                            ) ?: '—'
                        );
                        ?>
                    </p>
                    <div class="tp-match-court-view__sets" data-tp-field="team2_sets">
                        <?php if ($sets === []) : ?>
                            <span class="tp-match-court-view__set-pill">—</span>
                        <?php else : ?>
                            <?php foreach ($sets as $set) : ?>
                                <span class="tp-match-court-view__set-pill">
                                    <?php echo esc_html((string) (int) $set['team2_score']); ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        window.tpMatchLive = <?php echo wp_json_encode([
            'restUrl' => rest_url('tornei-padel/v1/live/' . $tournament['slug'] . '/match/' . (int) $match['id']),
            'pollMs'  => 10000,
        ]); ?>;
    </script>
</main>
<?php
get_footer();
