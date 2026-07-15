<?php

/**

 * Riga partita — live partial.

 *

 * @package TorneiPadel

 *

 * @var array<string, mixed> $match

 * @var string $tournament_slug

 */



if (! defined('ABSPATH')) {

    exit;

}



$sets  = TP_Match::sets_for_match((int) $match['id']);

$score = TP_Match_Result_Service::format_score_label($sets);

$status_labels = [

    'scheduled' => __('Programmata', 'tornei-padel'),

    'live'      => __('IN CORSO', 'tornei-padel'),

    'finished'  => __('Terminata', 'tornei-padel'),

];

$status_label = $status_labels[$match['status']] ?? $match['status'];

$round        = (string) ($match['round_name'] ?? '');

$tournament_slug = $tournament_slug ?? '';

?>

<li class="tp-match-item tp-match-item--<?php echo esc_attr($match['status']); ?>">

    <?php if ($round !== '') : ?>

        <div class="tp-match-round"><?php echo esc_html($round); ?></div>

    <?php endif; ?>

    <div class="tp-match-teams">

        <?php echo esc_html(($match['team1_name'] ?? 'TBD') . ' vs ' . ($match['team2_name'] ?? 'TBD')); ?>

    </div>

    <?php include TP_PLUGIN_DIR . 'includes/public/views/partials/live-match-meta.php'; ?>

</li>

