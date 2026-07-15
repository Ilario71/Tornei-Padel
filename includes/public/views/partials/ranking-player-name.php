<?php
/**
 * Nome giocatore con badge livello ELO.
 *
 * @package TorneiPadel
 *
 * @var string $name
 * @var int    $elo
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<span class="tp-rank-player-name">
    <span class="tp-rank-player-name__text"><?php echo esc_html($name); ?></span>
    <?php include __DIR__ . '/ranking-level-badge.php'; ?>
</span>
