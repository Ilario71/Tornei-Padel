<?php
/**
 * Badge livello ELO.
 *
 * @package TorneiPadel
 *
 * @var int $elo
 */

if (! defined('ABSPATH')) {
    exit;
}

$level = TP_Ranking_Level_Service::from_elo($elo);
$title = $level['label'] . ' · ' . $level['range_label'] . ' · ' . $level['meaning'];
?>
<span
    class="tp-rank-badge tp-rank-badge--<?php echo esc_attr($level['slug']); ?>"
    title="<?php echo esc_attr($title); ?>"
    aria-label="<?php echo esc_attr($title); ?>"
><?php echo esc_html($level['label']); ?></span>
