<?php
/**
 * Pulsante Segui + meta partita.
 *
 * @package TorneiPadel
 *
 * @var array<string, mixed> $match
 * @var string $tournament_slug
 */

if (! defined('ABSPATH')) {
    exit;
}

$tournament_slug = $tournament_slug ?? '';
?>
<div class="tp-match-meta">
    <div class="tp-match-meta__left">
        <?php if (! empty($match['court_name'])) : ?>
            <span class="tp-match-court"><?php echo esc_html($match['court_name']); ?></span>
        <?php endif; ?>
        <span class="tp-status-badge"><?php echo esc_html($status_label); ?></span>
        <?php if ($score !== '') : ?>
            <span class="tp-match-score"><?php echo esc_html($score); ?></span>
        <?php endif; ?>
        <?php if ($match['status'] === 'finished' && ! empty($match['winner_name'])) : ?>
            <span class="tp-match-winner">→ <?php echo esc_html($match['winner_name']); ?></span>
        <?php endif; ?>
    </div>
    <?php if ($tournament_slug !== '') : ?>
        <a
            class="tp-btn tp-btn--segui"
            href="<?php echo esc_url(TP_Match::match_url($tournament_slug, (int) $match['id'])); ?>"
        >
            <?php esc_html_e('Segui', 'tornei-padel'); ?>
        </a>
    <?php endif; ?>
</div>
